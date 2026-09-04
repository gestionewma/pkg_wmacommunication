<?php
defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Factory;

class Com_WmacommunicationInstallerScript
{
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if (version_compare(JVERSION, '6.0', '<')) {
            echo '<p style="color:red;">WMA Communication richiede Joomla 6.0 o superiore.</p>';
            return false;
        }

        return true;
    }

    public function update(InstallerAdapter $adapter): void
    {
        $db      = \Joomla\CMS\Factory::getDbo();
        $columns = $db->getTableColumns('#__wmacommunication_forms');

        if (!array_key_exists('checked_out', $columns)) {
            $db->setQuery('ALTER TABLE `#__wmacommunication_forms` ADD COLUMN `checked_out` INT(11) UNSIGNED DEFAULT NULL AFTER `modified_by`')->execute();
        }

        if (!array_key_exists('checked_out_time', $columns)) {
            $db->setQuery('ALTER TABLE `#__wmacommunication_forms` ADD COLUMN `checked_out_time` DATETIME DEFAULT NULL AFTER `checked_out`')->execute();
        }

        // Joomla 6: le colonne di check-out devono essere nullable (standard Table moderno)
        $db->setQuery('ALTER TABLE `#__wmacommunication_forms` '
            . 'MODIFY `checked_out` INT(11) UNSIGNED DEFAULT NULL, '
            . 'MODIFY `checked_out_time` DATETIME DEFAULT NULL')->execute();
        $db->setQuery('UPDATE `#__wmacommunication_forms` SET `checked_out` = NULL WHERE `checked_out` = 0')->execute();
        $db->setQuery("UPDATE `#__wmacommunication_forms` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00'")->execute();

        // Tabella allegati (storage privato)
        $db->setQuery(
            "CREATE TABLE IF NOT EXISTS `#__wmacommunication_uploads` (\n"
            . "  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
            . "  `token` VARCHAR(64) NOT NULL,\n"
            . "  `form_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
            . "  `field_uid` VARCHAR(64) NOT NULL DEFAULT '',\n"
            . "  `original_name` VARCHAR(255) NOT NULL DEFAULT '',\n"
            . "  `stored_name` VARCHAR(255) NOT NULL DEFAULT '',\n"
            . "  `subdir` VARCHAR(255) NOT NULL DEFAULT '',\n"
            . "  `mime` VARCHAR(150) NOT NULL DEFAULT '',\n"
            . "  `size` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
            . "  `created` DATETIME NOT NULL,\n"
            . "  `created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
            . "  `downloads` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
            . "  `last_download` DATETIME DEFAULT NULL,\n"
            . "  PRIMARY KEY (`id`),\n"
            . "  UNIQUE KEY `idx_token` (`token`),\n"
            . "  KEY `idx_created` (`created`)\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
        )->execute();

        // Tabella libreria template messaggio
        $db->setQuery(
            "CREATE TABLE IF NOT EXISTS `#__wmacommunication_templates` (\n"
            . "  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
            . "  `title` VARCHAR(255) NOT NULL DEFAULT '',\n"
            . "  `body` LONGTEXT,\n"
            . "  `state` TINYINT(1) NOT NULL DEFAULT 1,\n"
            . "  `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',\n"
            . "  `created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
            . "  `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',\n"
            . "  `modified_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
            . "  PRIMARY KEY (`id`),\n"
            . "  KEY `idx_state` (`state`)\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
        )->execute();

        // Tabella invii salvati (F1)
        $db->setQuery(
            "CREATE TABLE IF NOT EXISTS `#__wmacommunication_submissions` (\n"
            . "  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
            . "  `form_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,\n"
            . "  `form_title` VARCHAR(255) NOT NULL DEFAULT '',\n"
            . "  `summary` VARCHAR(500) NOT NULL DEFAULT '',\n"
            . "  `data` MEDIUMTEXT,\n"
            . "  `col1_label` VARCHAR(255) NOT NULL DEFAULT '',\n"
            . "  `col1_value` VARCHAR(500) NOT NULL DEFAULT '',\n"
            . "  `col2_label` VARCHAR(255) NOT NULL DEFAULT '',\n"
            . "  `col2_value` VARCHAR(500) NOT NULL DEFAULT '',\n"
            . "  `ip` VARCHAR(45) NOT NULL DEFAULT '',\n"
            . "  `is_read` TINYINT(1) NOT NULL DEFAULT 0,\n"
            . "  `created` DATETIME NOT NULL,\n"
            . "  PRIMARY KEY (`id`),\n"
            . "  KEY `idx_form_id` (`form_id`),\n"
            . "  KEY `idx_created` (`created`)\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci"
        )->execute();

        // Colonne colonna1/colonna2 (F1b) per chi ha già la tabella da un update precedente.
        // La CREATE TABLE IF NOT EXISTS sopra garantisce che la tabella esista già a
        // questo punto: niente bisogno di verificarlo, solo delle singole colonne nuove.
        try {
            $subColumns = $db->getTableColumns('#__wmacommunication_submissions');
            foreach (['col1_label' => 'VARCHAR(255)', 'col1_value' => 'VARCHAR(500)', 'col2_label' => 'VARCHAR(255)', 'col2_value' => 'VARCHAR(500)'] as $col => $def) {
                if (!array_key_exists($col, $subColumns)) {
                    $db->setQuery("ALTER TABLE `#__wmacommunication_submissions` ADD COLUMN `{$col}` {$def} NOT NULL DEFAULT '' AFTER `data`")->execute();
                }
            }
        } catch (\Throwable $e) {
            // best effort: non deve bloccare l'installazione
        }
    }

    public function postflight(string $type, InstallerAdapter $adapter): void
    {
        $this->deployMediaFiles($adapter);

        $this->installSamples($adapter);

        $this->installTemplateSamples($adapter);

        $this->prepareAttachmentsDir();

        $manifest = $adapter->getManifest();
        $version  = $manifest instanceof \SimpleXMLElement ? (string) $manifest->version : '';

        $mediaUrl = \Joomla\CMS\Uri\Uri::root() . 'media/com_wmacommunication/images/logo-wma.png';

        $langTag = Factory::getApplication()->getLanguage()->getTag();
        $t = $this->getPostflightTranslations($langTag, $type);

        echo '
        <div style="margin:2rem 0; padding:2rem; background:#f8f9fa; border-left:4px solid #559907; border-radius:4px; font-family:sans-serif;">
            <div style="margin-bottom:1.5rem;">
                <img src="' . $mediaUrl . '" alt="WMA Web Maker Agency" style="max-height:60px;">
            </div>
            <h2 style="margin:0 0 .5rem; color:#559907;">WMA Communication v' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . ' ' . $t['action_title'] . '</h2>
            <p style="margin:0 0 1rem; color:#495057;">' . $t['thanks'] . '</p>
            <p style="margin:0 0 .5rem; color:#495057;"><strong>' . $t['features_heading'] . '</strong></p>
            <ul style="color:#495057; margin:0 0 1.5rem; padding-left:1.25rem;">
                <li>' . $t['feature1'] . '</li>
                <li>' . $t['feature2'] . '</li>
            </ul>
            <div style="margin:0 0 1.5rem; padding:.75rem 1rem; background:#fff3e0; border-left:4px solid #ff9800; border-radius:4px; color:#e65100; font-size:.9rem;">
                ' . $t['samples_warning'] . '
            </div>
            <p style="margin:0; font-size:.9rem; color:#6c757d;">
                ' . $t['hcaptcha'] . '
            </p>
        </div>';
    }

    private function getPostflightTranslations(string $langTag, string $type): array
    {
        $isInstall = ($type === 'install');

        // Français
        if (str_starts_with($langTag, 'fr')) {
            return [
                'action_title'     => $isInstall ? 'installée avec succès !' : 'mise à jour avec succès !',
                'thanks'           => "Merci d'avoir choisi WMA Communication, l'extension native pour Joomla 6 dédiée à la gestion avancée des formulaires de contact.",
                'features_heading' => 'Avec cet outil vous pouvez :',
                'feature1'         => 'Créer des formulaires personnalisés en quelques étapes.',
                'feature2'         => "Profiter pleinement de l'architecture moderne de Joomla.",
                'samples_warning'  => '<strong>Attention :</strong> Les formulaires d\'exemple ont été importés avec le statut <strong>Non publié</strong>. Avant de les personnaliser, nous vous recommandons de <strong>les renommer</strong> pour éviter les conflits lors des futures mises à jour.',
                'hcaptcha'         => 'N\'oubliez pas de saisir vos clés hCaptcha dans <strong>Composants → WMA Communication → Options</strong>.',
            ];
        }

        // English
        if (str_starts_with($langTag, 'en')) {
            return [
                'action_title'     => $isInstall ? 'installed successfully!' : 'updated successfully!',
                'thanks'           => 'Thank you for choosing WMA Communication, the native Joomla 6 extension for advanced contact form management.',
                'features_heading' => 'With this tool you can:',
                'feature1'         => 'Create custom forms in a few steps.',
                'feature2'         => "Take full advantage of Joomla's modern architecture.",
                'samples_warning'  => '<strong>Warning:</strong> Sample forms have been imported as <strong>Unpublished</strong>. Before customizing them, we recommend <strong>renaming them</strong> to avoid conflicts in future updates.',
                'hcaptcha'         => 'Remember to set your hCaptcha keys in <strong>Components → WMA Communication → Options</strong>.',
            ];
        }

        // Default: Italiano
        return [
            'action_title'     => $isInstall ? 'installata con successo!' : 'aggiornata con successo!',
            'thanks'           => "Grazie per aver scelto WMA Communication, l'estensione nativa per Joomla 6 dedicata alla gestione avanzata dei form di contatto.",
            'features_heading' => 'Con questo strumento potrai:',
            'feature1'         => 'Creare form personalizzati in pochi step.',
            'feature2'         => "Sfruttare la piena compatibilità con l'architettura moderna di Joomla.",
            'samples_warning'  => '<strong>Attenzione:</strong> I form di esempio sono stati importati con stato <strong>Non pubblicato</strong>. Prima di personalizzarli, ti consigliamo di <strong>rinominarli</strong> per evitare conflitti in futuri aggiornamenti.',
            'hcaptcha'         => 'Ricorda di inserire le chiavi hCaptcha in <strong>Componenti → WMA Communication → Opzioni</strong>.',
        ];
    }

    /**
     * Crea e "blinda" la cartella base degli allegati (fuori dal web root se possibile).
     */
    private function prepareAttachmentsDir(): void
    {
        $helper = JPATH_SITE . '/components/com_wmacommunication/src/Helper/AttachmentHelper.php';

        if (is_file($helper)) {
            require_once $helper;
            if (class_exists('\\Wma\\Component\\Wmacommunication\\Site\\Helper\\AttachmentHelper')) {
                try {
                    \Wma\Component\Wmacommunication\Site\Helper\AttachmentHelper::baseDir();
                    return;
                } catch (\Throwable $e) {
                    // fallback qui sotto
                }
            }
        }

        // @ obbligatorio: con open_basedir attivo, controllare/creare un percorso
        // fuori dai path consentiti genera un Warning visibile, non solo "false".
        $dir = \dirname(JPATH_ROOT) . '/_wmacommunication-uploads';
        if (!@is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $dir = JPATH_ROOT . '/_wmacommunication-uploads';
            @mkdir($dir, 0755, true);
        }

        if (@is_dir($dir) && !@is_file($dir . '/.htaccess')) {
            @file_put_contents(
                $dir . '/.htaccess',
                "Options -Indexes -ExecCGI\n"
                . "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n"
                . "<IfModule mod_php.c>\n\tphp_flag engine off\n</IfModule>\n"
            );
            @file_put_contents($dir . '/index.html', "<!DOCTYPE html><title></title>\n");
        }
    }

    private function installSamples(InstallerAdapter $adapter): void
    {
        $source   = $adapter->getParent()->getPath('source');
        $samplesDir = $source . '/admin/forms/samples';

        if (!is_dir($samplesDir)) {
            return;
        }

        $files = glob($samplesDir . '/*.json');

        if (empty($files)) {
            return;
        }

        $db  = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
        $app = \Joomla\CMS\Factory::getApplication();
        $now = \Joomla\CMS\Factory::getDate()->toSql();
        $userId = $app->getIdentity() ? $app->getIdentity()->id : 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data    = json_decode($content, true);

            if (!$data || !isset($data['title'])) {
                continue;
            }

            // Salta se esiste già un form con lo stesso titolo
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__wmacommunication_forms'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));
            $exists = (int) $db->setQuery($query)->loadResult();

            if ($exists) {
                continue;
            }

            unset($data['id']);

            // Scarta qualsiasi chiave che non sia una colonna reale della tabella.
            $allowedColumns = [
                'title', 'alias', 'description', 'fields', 'settings',
                'recipient_email', 'email_subject', 'success_message', 'state',
                'created', 'created_by', 'modified', 'modified_by',
                'checked_out', 'checked_out_time',
            ];
            $data = array_intersect_key($data, array_flip($allowedColumns));

            $data['state']       = 0;
            $data['created']     = $now;
            $data['created_by']  = $userId;
            $data['modified']    = $now;
            $data['modified_by'] = $userId;
            $data['checked_out'] = null;
            $data['checked_out_time'] = null;

            $columns = array_keys($data);
            $values  = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($data));

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__wmacommunication_forms'))
                ->columns(array_map(fn($c) => $db->quoteName($c), $columns))
                ->values(implode(',', $values));
            $db->setQuery($query)->execute();
        }
    }

    /**
     * Installa i template messaggio di serie. A differenza dei form-sample
     * (che diventano pagine pubbliche) un template è testo inerte: non ha
     * effetto finché un admin non lo carica in un form, quindi si installa
     * già pubblicato.
     */
    private function installTemplateSamples(InstallerAdapter $adapter): void
    {
        $source     = $adapter->getParent()->getPath('source');
        $samplesDir = $source . '/admin/msgtemplates/samples';

        if (!is_dir($samplesDir)) {
            return;
        }

        $files = glob($samplesDir . '/*.json');

        if (empty($files)) {
            return;
        }

        $db     = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
        $app    = \Joomla\CMS\Factory::getApplication();
        $now    = \Joomla\CMS\Factory::getDate()->toSql();
        $userId = $app->getIdentity() ? $app->getIdentity()->id : 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data    = json_decode($content, true);

            if (!$data || !isset($data['title'])) {
                continue;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__wmacommunication_templates'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));
            $exists = (int) $db->setQuery($query)->loadResult();

            if ($exists) {
                continue;
            }

            unset($data['id']);
            $data = array_intersect_key($data, array_flip(['title', 'body']));

            $data['state']       = 1;
            $data['created']     = $now;
            $data['created_by']  = $userId;
            $data['modified']    = $now;
            $data['modified_by'] = $userId;

            $columns = array_keys($data);
            $values  = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($data));

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__wmacommunication_templates'))
                ->columns(array_map(fn($c) => $db->quoteName($c), $columns))
                ->values(implode(',', $values));
            $db->setQuery($query)->execute();
        }
    }

    private function deployMediaFiles(InstallerAdapter $adapter): void
    {
        $source = $adapter->getParent()->getPath('source');
        $mediaSource = $source . '/media';
        $mediaDest = JPATH_ROOT . '/media/com_wmacommunication';

        if (!is_dir($mediaSource)) {
            return;
        }

        // Remove existing media folder
        if (is_dir($mediaDest)) {
            $this->deleteRecursive($mediaDest);
        }

        // Re-create and copy fresh files
        mkdir($mediaDest, 0755, true);
        $this->copyRecursive($mediaSource, $mediaDest);
    }

    private function deleteRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }

    private function copyRecursive(string $src, string $dst): void
    {
        $dir = opendir($src);
        if ($dir === false) {
            return;
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;

            if (is_dir($srcFile)) {
                mkdir($dstFile, 0755, true);
                $this->copyRecursive($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }

        closedir($dir);
    }
}
