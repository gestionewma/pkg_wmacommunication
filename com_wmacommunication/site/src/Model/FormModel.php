<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @author      WMA Web Maker Agency
 * @copyright   (C) 2026 WMA Web Maker Agency
 * @license     GNU General Public License version 2 or later
 * @link        https://www.webmakeragency.com
 * @version     2.0.2
 * @date        02/07/2026
 */

namespace Wma\Component\Wmacommunication\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Wma\Component\Wmacommunication\Site\Helper\AttachmentHelper;

class FormModel extends BaseDatabaseModel
{
    public function getForm(int $id): ?\stdClass
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__wmacommunication_forms'))
            ->where($db->quoteName('id') . ' = ' . (int) $id)
            ->where($db->quoteName('state') . ' = 1');

        $form = $db->setQuery($query)->loadObject();

        if (!$form) {
            return null;
        }

        $form->fields_decoded = [];
        if (!empty($form->fields)) {
            $decoded = json_decode($form->fields, true);
            if (is_array($decoded)) {
                $form->fields_decoded = $decoded;
            }
        }

        $form->settings_decoded = [];
        if (!empty($form->settings)) {
            $decoded = json_decode($form->settings, true);
            if (is_array($decoded)) {
                $form->settings_decoded = $decoded;
            }
        }

        return $form;
    }

    public function getSettings(int $formId): array
    {
        $form = $this->getForm($formId);
        return $form ? $form->settings_decoded : [];
    }

    public function processSubmission(array $data, int $formId): array|bool|string
    {
        $form = $this->getForm($formId);

        if (!$form) {
            return Text::_('COM_WMACOMMUNICATION_FORM_NOT_FOUND');
        }

        $fields   = $form->fields_decoded;
        $settings = $this->resolveLanguageSettings($form->settings_decoded);

        $langTag      = Factory::getApplication()->getLanguage()->getTag();
        $translations = $form->settings_decoded['translations'] ?? [];

        $hasUpload = (bool) array_filter($fields, static fn($f) => ($f['type'] ?? '') === 'fileupload');
        if ($hasUpload) {
            $this->purgeExpiredAttachments();
        }

        // --- Verifica hCaptcha ---
        foreach ($fields as $index => $field) {
            if ($field['type'] === 'hcaptcha') {
                $token     = $data['h-captcha-response'] ?? '';
                $secretKey = ComponentHelper::getParams('com_wmacommunication')->get('hcaptcha_secret_key', '');
                if (!$this->verifyHcaptcha($token, $secretKey)) {
                    return Text::_('COM_WMACOMMUNICATION_HCAPTCHA_FAILED');
                }
            }
        }

        // --- Validazione campi obbligatori ---
        foreach ($fields as $index => $field) {
            $type     = $field['type'] ?? '';
            $required = !empty($field['required']);

            if (!$required) {
                continue;
            }

            if (in_array($type, ['html', 'heading', 'divider', 'emptyspace', 'hcaptcha', 'submit', 'privacy'])) {
                continue;
            }

            $uid   = $field['uid'] ?? ('idx' . $index);
            $name  = 'wma_field_' . $uid;
            $label = Text::_($this->pickTranslation($translations, $langTag, 'field.' . $uid . '.label', $field['label'] ?? ''));

            if ($type === 'fileupload') {
                $file = $_FILES[$name] ?? null;
                if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE || empty($file['size'])) {
                    return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_REQUIRED'), $label);
                }
                continue;
            }

            $value = $data[$name] ?? '';
            $empty = is_array($value) ? empty(array_filter($value)) : trim((string) $value) === '';

            if ($empty) {
                return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_REQUIRED'), $label);
            }
        }

        // --- Validazione formato / valori ammessi (indipendente da "obbligatorio") ---
        foreach ($fields as $index => $field) {
            $type = $field['type'] ?? '';
            $uid  = $field['uid'] ?? ('idx' . $index);
            $name = 'wma_field_' . $uid;
            $raw  = $data[$name] ?? '';

            if (!in_array($type, ['email', 'url', 'tel', 'number', 'dropdown', 'radio', 'checkbox', 'office'], true)) {
                continue;
            }

            $label = Text::_($this->pickTranslation($translations, $langTag, 'field.' . $uid . '.label', $field['label'] ?? ''));

            if ($type === 'email') {
                $v = trim((string) $raw);
                if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_INVALID_EMAIL'), $label);
                }
                continue;
            }

            if ($type === 'url') {
                $v = trim((string) $raw);
                if ($v !== '' && !filter_var($v, FILTER_VALIDATE_URL)) {
                    return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_INVALID_URL'), $label);
                }
                continue;
            }

            if ($type === 'tel') {
                $v = trim((string) $raw);
                if ($v !== '' && !preg_match('#^[0-9+()./\s-]{5,40}$#', $v)) {
                    return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_INVALID_TEL'), $label);
                }
                continue;
            }

            if ($type === 'number') {
                $v = trim((string) $raw);
                if ($v !== '') {
                    if (!is_numeric($v)) {
                        return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_INVALID_NUMBER'), $label);
                    }
                    if (($field['min'] ?? '') !== '' && (float) $v < (float) $field['min']) {
                        return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_NUMBER_MIN'), $label, $field['min']);
                    }
                    if (($field['max'] ?? '') !== '' && (float) $v > (float) $field['max']) {
                        return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_NUMBER_MAX'), $label, $field['max']);
                    }
                }
                continue;
            }

            // dropdown / radio / checkbox / office: il valore inviato deve essere una delle opzioni
            if ($type === 'office') {
                $allowed = [];
                foreach (explode("\n", $field['options'] ?? '') as $opt) {
                    $parts = explode('|', $opt);
                    $e     = trim($parts[1] ?? '');
                    if ($e !== '') {
                        $allowed[] = $e;
                    }
                }
            } else {
                $allowed = $this->optionList($this->pickTranslation($translations, $langTag, 'field.' . $uid . '.options', $field['options'] ?? ''));
            }

            $submitted = is_array($raw) ? $raw : ($raw !== '' ? [$raw] : []);
            foreach ($submitted as $one) {
                if (!in_array(trim((string) $one), $allowed, true)) {
                    return sprintf(Text::_('COM_WMACOMMUNICATION_FIELD_INVALID_CHOICE'), $label);
                }
            }
        }

        // --- Raccolta valori campi ---
        $fieldValues = [];
        $officeEmail = '';

        foreach ($fields as $index => $field) {
            $type  = $field['type'] ?? '';
            $uid   = $field['uid'] ?? ('idx' . $index);
            $name  = 'wma_field_' . $uid;
            $label = Text::_($this->pickTranslation($translations, $langTag, 'field.' . $uid . '.label', $field['label'] ?? ''));

            if (in_array($type, ['html','heading','divider','emptyspace','hcaptcha','submit'])) {
                continue;
            }

            if ($type === 'privacy') {
                if (empty($data[$name])) {
                    return Text::_('COM_WMACOMMUNICATION_PRIVACY_REQUIRED');
                }

                $fieldValues[$index] = [
                    'label'     => $label,
                    'raw_label' => $field['label'] ?? '',
                    'value'     => Text::_('COM_WMACOMMUNICATION_PRIVACY_ACCEPTED'),
                    'type'      => $type,
                    'name'      => $name,
                ];
                continue;
            }

            // --- Campo fileupload ---
            if ($type === 'fileupload') {
                $file  = $_FILES[$name] ?? null;
                $value = '';

                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $stored = $this->storeAttachment($file, $field, $uid, $formId);
                    if (is_string($stored)) {
                        return $stored;
                    }
                    // valore = nome originale | token (il percorso reale non esce mai)
                    $value = $stored['original'] . '|' . $stored['token'];
                } elseif ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                    return Text::_('COM_WMACOMMUNICATION_FILE_UPLOAD_ERROR');
                }

                $fieldValues[$index] = [
                    'label'     => $label,
                    'raw_label' => $field['label'] ?? '',
                    'value'     => $value,
                    'type'      => $type,
                    'name'      => $name,
                ];
                continue;
            }

            $value = $data[$name] ?? '';
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // --- Campo office ---
            if ($type === 'office') {
                $selectedEmail = trim((string) $value);
                $officeName    = '';

                foreach (explode("\n", $field['options'] ?? '') as $opt) {
                    $parts  = explode('|', $opt);
                    $oEmail = trim($parts[1] ?? '');
                    if ($oEmail === $selectedEmail) {
                        $officeName = trim($parts[0]);
                        break;
                    }
                }

                $officeToIndex = $settings['officeto'] ?? '';
                if ($officeToIndex !== '' && (int)$officeToIndex === $index && !empty($selectedEmail)) {
                    $officeEmail = $selectedEmail;
                }

                $fieldValues[$index] = [
                    'label'     => $label,
                    'raw_label' => $field['label'] ?? '',
                    'value'     => $officeName ?: $selectedEmail,
                    'type'      => $type,
                    'name'      => $name,
                ];
                continue;
            }

            $fieldValues[$index] = [
                'label'     => $label,
                'raw_label' => $field['label'] ?? '',
                'value'     => strip_tags((string) $value),
                'type'      => $type,
                'name'      => $name,
            ];
        }

        // --- Modalità di invio: email (default) | email_db | db ---
        $deliveryMode = $settings['delivery_mode'] ?? 'email';
        $saveToDb     = in_array($deliveryMode, ['email_db', 'db'], true);
        $sendEmail    = $deliveryMode !== 'db';

        if ($saveToDb) {
            $this->purgeExpiredSubmissions();
            $this->storeSubmission($form, $formId, $fieldValues, $fields);
        }

        if (!$sendEmail) {
            return ['success' => true, 'fieldValues' => $fieldValues, 'settings' => $settings];
        }

        // --- Costruzione corpo email ---
        $emailBody = $this->buildEmailBody($settings, $fieldValues, $fields);

        // --- Destinatari ---
        $fixedTo = trim($settings['to'] ?? '');
        $cc      = trim($settings['cc'] ?? '');
        $ccn     = trim($settings['ccn'] ?? '');

        $officeToIndex = $settings['officeto'] ?? '';
        if (!empty($officeToIndex) && !empty($officeEmail)) {
            $to = $officeEmail;
        } else {
            $to = $fixedTo;
        }


        if (empty($to)) {
            return Text::_('COM_WMACOMMUNICATION_SENDING_NO_RECIPIENT');
        }

        // --- Copia per lo scrivente ---
        $replyToIndex = $settings['replyto'] ?? '';
        $replyToEmail = '';
        if ($replyToIndex !== '') {
            $maybeEmail = trim($fieldValues[(int)$replyToIndex]['value'] ?? '');
            if (filter_var($maybeEmail, FILTER_VALIDATE_EMAIL)) {
                $replyToEmail = $maybeEmail;
            }
        }

        // --- Oggetto email ---
            $subjectFieldValue = '';
            foreach ($fieldValues as $fv) {
                if (strtolower($fv['label']) === 'oggetto') {
                    $subjectFieldValue = $fv['value'];
                    break;
                }
            }
        $subject = !empty($subjectFieldValue)
            ? $subjectFieldValue
            : $this->pickTranslation($translations, $langTag, 'form.title', (string) $form->title);
        $subject = preg_replace('/[\r\n]+/', ' ', $subject);

        // --- Nome mittente ---
        $senderName = trim($settings['sender_name'] ?? '');

        // --- Invio ---

        // --- Invio ---
        try {
            $app    = Factory::getApplication();
            $config = $app->getConfig();

            $fromEmail = $config->get('mailfrom');
            $fromName  = !empty($senderName) ? $senderName : $config->get('fromname');

            // --- Mail principale al destinatario ---
            $mailer1 = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $mailer1->isHTML(true);
            $mailer1->setSubject($subject);
            $mailer1->setBody($emailBody);
            $mailer1->setSender([$fromEmail, $fromName]);

            foreach (array_map('trim', explode(',', $to)) as $email) {
                if ($email) $mailer1->addRecipient($email);
            }

            if ($cc) {
                foreach (array_map('trim', explode(',', $cc)) as $email) {
                    if ($email) $mailer1->addCc($email);
                }
            }

            if ($ccn) {
                foreach (array_map('trim', explode(',', $ccn)) as $email) {
                    if ($email) $mailer1->addBcc($email);
                }
            }

            // Reply-To = email dello scrivente
            if ($replyToEmail) {
                $mailer1->addReplyTo($replyToEmail);
            }

            $mailer1->Send();

            // --- Mail di copia allo scrivente ---
            if ($replyToEmail) {
                $mailer2 = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
                $mailer2->isHTML(true);
                $mailer2->setSubject($subject);
                $mailer2->setBody($emailBody);
                $mailer2->setSender([$fromEmail, $fromName]);
                $mailer2->addRecipient($replyToEmail);

                // Reply-To = email del destinatario
                foreach (array_map('trim', explode(',', $to)) as $email) {
                    if ($email) {
                        $mailer2->addReplyTo($email);
                        break;
                    }
                }

                $mailer2->Send();
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return ['success' => true, 'fieldValues' => $fieldValues, 'settings' => $settings];
    }

    /**
     * Salva un allegato nello storage privato e registra la riga in
     * #__wmacommunication_uploads. Ritorna ['token' => ..., 'original' => ...]
     * oppure una stringa di errore da mostrare all'utente.
     *
     * @return array{token: string, original: string}|string
     */
    private function storeAttachment(array $file, array $field, string $uid, int $formId): array|string
    {
        $origName = basename(str_replace('\\', '/', (string) $file['name']));
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if ($ext === '' || strpos($origName, "\0") !== false
            || in_array($ext, AttachmentHelper::blockedTypes(), true)) {
            return sprintf(Text::_('COM_WMACOMMUNICATION_FILE_TYPE_NOT_ALLOWED'), $ext);
        }

        $uploadTypes = trim($field['upload_types'] ?? '');
        $allowed = $uploadTypes !== ''
            ? array_filter(array_map(static fn($v) => strtolower(ltrim(trim($v), '.')), explode(',', $uploadTypes)))
            : AttachmentHelper::defaultAllowedTypes();

        if (!in_array($ext, $allowed, true)) {
            return sprintf(Text::_('COM_WMACOMMUNICATION_FILE_TYPE_NOT_ALLOWED'), $ext);
        }

        if (!\Joomla\CMS\Filter\InputFilter::isSafeFile($file)) {
            return sprintf(Text::_('COM_WMACOMMUNICATION_FILE_TYPE_NOT_ALLOWED'), $ext);
        }

        $maxSize = (int) ($field['max_file_size'] ?? 0) * 1024 * 1024;
        if ($maxSize > 0 && $file['size'] > $maxSize) {
            return Text::_('COM_WMACOMMUNICATION_FILE_TOO_LARGE');
        }

        // Sottocartella: "upload_folder" opzionale (organizzativa) + data + segmento random
        $folder = preg_replace('#\.\.+#', '', str_replace('\\', '/', (string) ($field['upload_folder'] ?? '')));
        $folder = trim(preg_replace('#/+#', '/', preg_replace('#[^A-Za-z0-9_\-/]#', '', (string) $folder)), '/');
        $subdir = ($folder !== '' ? $folder . '/' : '') . gmdate('Y/m') . '/' . bin2hex(random_bytes(8));

        $base    = AttachmentHelper::baseDir();
        $destDir = $base . '/' . $subdir;

        $realBase = str_replace('\\', '/', realpath($base) ?: $base);
        if (strpos(str_replace('\\', '/', $destDir) . '/', $realBase . '/') !== 0) {
            return Text::_('COM_WMACOMMUNICATION_FILE_PATH_INVALID');
        }

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            return Text::_('COM_WMACOMMUNICATION_FILE_CREATE_FOLDER_FAILED');
        }

        $storedName = bin2hex(random_bytes(12)) . '.' . $ext;
        $destPath   = $destDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return Text::_('COM_WMACOMMUNICATION_FILE_UPLOAD_FAILED');
        }
        @chmod($destPath, 0644);

        $token = bin2hex(random_bytes(32));
        $app   = Factory::getApplication();

        $row = (object) [
            'token'         => $token,
            'form_id'       => $formId,
            'field_uid'     => substr($uid, 0, 64),
            'original_name' => substr($origName, 0, 255),
            'stored_name'   => $storedName,
            'subdir'        => substr($subdir, 0, 255),
            'mime'          => substr((string) ($file['type'] ?? ''), 0, 150),
            'size'          => (int) $file['size'],
            'created'       => Factory::getDate()->toSql(),
            'created_by'    => (int) ($app->getIdentity()->id ?? 0),
            'downloads'     => 0,
            'last_download' => null,
        ];

        $this->getDatabase()->insertObject('#__wmacommunication_uploads', $row);

        return ['token' => $token, 'original' => $origName];
    }

    /**
     * Salva l'invio nella tabella #__wmacommunication_submissions (F1).
     * Best effort: un errore non deve bloccare l'invio del form.
     */
    private function storeSubmission(\stdClass $form, int $formId, array $fieldValues, array $fields = []): void
    {
        try {
            $data = [];
            foreach ($fieldValues as $fv) {
                if (in_array($fv['type'] ?? '', ['html', 'heading', 'divider', 'emptyspace', 'hcaptcha', 'submit'], true)) {
                    continue;
                }
                $data[] = [
                    'label' => $fv['label'] ?? '',
                    'value' => $fv['value'] ?? '',
                    'type'  => $fv['type'] ?? '',
                ];
            }

            $summaryParts = [];
            foreach ($data as $d) {
                if ($d['type'] === 'fileupload' || trim((string) $d['value']) === '') {
                    continue;
                }
                $summaryParts[] = $d['value'];
                if (count($summaryParts) >= 2) {
                    break;
                }
            }
            $summary = mb_substr(implode(' — ', $summaryParts), 0, 500);

            // --- Colonne elenco (F1b): campi testo/email marcati con "list_column" (1 o 2) ---
            $columns = ['1' => ['label' => '', 'value' => ''], '2' => ['label' => '', 'value' => '']];
            foreach ($fields as $index => $field) {
                $col = (string) ($field['list_column'] ?? '');
                if ($col === '' || !isset($columns[$col]) || $columns[$col]['label'] !== '') {
                    continue;
                }
                if (!in_array($field['type'] ?? '', ['text', 'email'], true)) {
                    continue;
                }
                $fv = $fieldValues[$index] ?? null;
                if ($fv === null) {
                    continue;
                }
                $columns[$col] = [
                    'label' => (string) ($fv['label'] ?? ''),
                    'value' => (string) ($fv['value'] ?? ''),
                ];
            }

            $app = Factory::getApplication();
            $row = (object) [
                'form_id'    => $formId,
                'form_title' => substr((string) $form->title, 0, 255),
                'summary'    => $summary,
                'data'       => json_encode($data, JSON_UNESCAPED_UNICODE),
                'col1_label' => substr($columns['1']['label'], 0, 255),
                'col1_value' => mb_substr($columns['1']['value'], 0, 500),
                'col2_label' => substr($columns['2']['label'], 0, 255),
                'col2_value' => mb_substr($columns['2']['value'], 0, 500),
                'ip'         => substr((string) $app->input->server->getString('REMOTE_ADDR', ''), 0, 45),
                'is_read'    => 0,
                'created'    => Factory::getDate()->toSql(),
            ];

            $this->getDatabase()->insertObject('#__wmacommunication_submissions', $row);
        } catch (\Throwable $e) {
            // best effort: il salvataggio non deve bloccare l'invio del form
        }
    }

    /**
     * Elimina gli invii più vecchi della retention configurata (0 = mai).
     * Best effort: un errore non deve bloccare l'invio del form.
     */
    private function purgeExpiredSubmissions(): void
    {
        $days = (int) ComponentHelper::getParams('com_wmacommunication')->get('submissions_retention', 0);
        if ($days <= 0) {
            return;
        }

        try {
            $db  = $this->getDatabase();
            $cut = Factory::getDate('-' . $days . ' days')->toSql();

            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__wmacommunication_submissions'))
                    ->where($db->quoteName('created') . ' < :cut')
                    ->bind(':cut', $cut)
            )->execute();
        } catch (\Throwable $e) {
            // best effort
        }
    }

    /**
     * Elimina allegati e righe più vecchi della retention configurata (0 = mai).
     * Best effort: un errore non deve bloccare l'invio del form.
     */
    private function purgeExpiredAttachments(): void
    {
        $days = AttachmentHelper::retentionDays();
        if ($days <= 0) {
            return;
        }

        try {
            $db  = $this->getDatabase();
            $cut = Factory::getDate('-' . $days . ' days')->toSql();

            $rows = $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName(['id', 'subdir', 'stored_name']))
                    ->from($db->quoteName('#__wmacommunication_uploads'))
                    ->where($db->quoteName('created') . ' < :cut')
                    ->bind(':cut', $cut),
                0,
                100
            )->loadObjectList();

            if (!$rows) {
                return;
            }

            $base     = AttachmentHelper::baseDir();
            $realBase = str_replace('\\', '/', realpath($base) ?: $base);
            $ids      = [];

            foreach ($rows as $r) {
                $p    = $base . '/' . ($r->subdir !== '' ? trim($r->subdir, '/') . '/' : '') . $r->stored_name;
                $real = realpath($p);
                if ($real !== false && strpos(str_replace('\\', '/', $real) , $realBase . '/') === 0) {
                    @unlink($real);
                }
                $ids[] = (int) $r->id;
            }

            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__wmacommunication_uploads'))
                    ->whereIn($db->quoteName('id'), $ids)
            )->execute();
        } catch (\Throwable $e) {
            // best effort
        }
    }

    private function formatFileValueForEmail(string $raw): string
    {
        $parts = explode('|', $raw, 2);
        if (count($parts) < 2) {
            return htmlspecialchars($raw);
        }

        $fileName = $parts[0];
        $token    = preg_replace('/[^a-f0-9]/', '', $parts[1]);

        if ($token === '') {
            return htmlspecialchars($fileName);
        }

        $url = \Joomla\CMS\Uri\Uri::root()
            . 'index.php?option=com_wmacommunication&task=download.attachment&token=' . $token;

        return '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($fileName) . '</a>';
    }

    public function resolvePlaceholders(string $text, array $fieldValues): string
    {
        foreach ($fieldValues as $fv) {
            $value = ($fv['type'] === 'fileupload' && !empty($fv['value']))
                ? $this->formatFileValueForEmail($fv['value'])
                : htmlspecialchars($fv['value']);
            $keys  = [];

            if (!empty($fv['raw_label'])) {
                $keys[] = $fv['raw_label'];
            }
            if (!empty($fv['label'])) {
                $keys[] = $fv['label'];
            }

            foreach (array_unique($keys) as $key) {
                $text = str_ireplace('{' . $key . '}', $value, $text);
            }
        }

        // Fallback: translate any remaining {COM_...} constants
        $text = preg_replace_callback(
            '/\{COM_[A-Z0-9_]+\}/i',
            function($matches) {
                return Text::_(trim($matches[0], '{}'));
            },
            $text
        );

        return $text;
    }

    private function buildEmailBody(array $settings, array $fieldValues, array $fields): string
    {
        $emailBody = trim($settings['email_body'] ?? '');

        if (!empty($emailBody)) {
            $emailBody = $this->resolvePlaceholders($emailBody, $fieldValues);

            // Converte i percorsi immagine relativi in assoluti
            $baseUrl   = rtrim(\Joomla\CMS\Uri\Uri::root(), '/') . '/';
            $emailBody = preg_replace_callback(
                '/src=["\'](?!https?:\/\/)(?!\/\/)([^"\']+)["\']/i',
                function($matches) use ($baseUrl) {
                    return 'src="' . $baseUrl . ltrim($matches[1], '/') . '"';
                },
                $emailBody
            );

            return $emailBody;
        }

        $body = '<html><body>';
        $body .= '<table style="width:100%;border-collapse:collapse;">';
        foreach ($fieldValues as $fv) {
            $displayValue = ($fv['type'] === 'fileupload' && !empty($fv['value']))
                ? $this->formatFileValueForEmail($fv['value'])
                : htmlspecialchars($fv['value']);
            $body .= '<tr>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;font-weight:bold;width:30%;">' . htmlspecialchars($fv['label']) . '</td>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . $displayValue . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
        $body .= '</body></html>';

        return $body;
    }

    /**
     * Risolve una stringa traducibile del form: traduzione della lingua attiva se
     * presente, altrimenti il valore della lingua base (passato in $base).
     */
    private function pickTranslation(array $translations, string $langTag, string $key, string $base): string
    {
        // La lingua base non ha traduzioni: i valori base sono inline
        if ($langTag === ($translations['_base'] ?? '')) {
            return $base;
        }

        $value = $translations[$langTag][$key] ?? '';

        return $value !== '' ? $value : $base;
    }

    /**
     * Elenco delle opzioni ammesse per dropdown/radio/checkbox, applicando la
     * stessa risoluzione (trim + Text::_) usata nel rendering.
     */
    private function optionList(string $raw): array
    {
        $out = [];
        foreach (explode("\n", $raw) as $opt) {
            $opt = trim(Text::_(trim($opt)));
            if ($opt !== '') {
                $out[] = $opt;
            }
        }

        return $out;
    }

    private function resolveLanguageSettings(array $settings): array
    {
        $langTag = Factory::getApplication()->getLanguage()->getTag();

        $base = $settings['messages_base']
            ?? ($settings['messages']['_base'] ?? $settings['messages']['all'] ?? []);

        // La lingua base non ha traduzioni: i valori base sono inline
        $isBaseLang = $langTag === ($settings['translations']['_base'] ?? '');
        $tr         = $isBaseLang ? [] : ($settings['translations'][$langTag] ?? []);

        $success = $tr['msg.success_msg'] ?? '';
        $body    = $tr['msg.email_body'] ?? '';

        $settings['success_msg'] = $success !== '' ? $success : ($base['success_msg'] ?? '');
        $settings['email_body']  = $body    !== '' ? $body    : ($base['email_body'] ?? '');

        return $settings;
    }

    private function verifyHcaptcha(string $token, string $secretKey): bool
    {
        if ($token === '' || $secretKey === '') {
            return false;
        }

        try {
            $response = HttpFactory::getHttp()->post(
                'https://hcaptcha.com/siteverify',
                ['secret' => $secretKey, 'response' => $token],
                [],
                10
            );
        } catch (\Throwable $e) {
            return false;
        }

        if ((int) $response->getStatusCode() !== 200) {
            return false;
        }

        $result = json_decode((string) $response->getBody(), true);

        return !empty($result['success']);
    }
}