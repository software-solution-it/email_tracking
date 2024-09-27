<?php
require_once __DIR__ . '/../models/Email.php';
require_once __DIR__ . '/../models/EmailAccount.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

class EmailSyncService {
    private $emailModel;
    private $emailAccountModel;

    public function __construct($db) {
        $this->emailModel = new Email($db);
        $this->emailAccountModel = new EmailAccount($db);
    }

    public function syncEmailsByUserIdAndProviderId($user_id, $provider_id) {
        set_time_limit(0);

        $emailAccount = $this->emailAccountModel->getEmailAccountByUserIdAndProviderId($user_id, $provider_id);
        
        if (!$emailAccount) {
            return ['status' => false, 'message' => 'Email account not found for the given provider'];
        }

        $mailbox = "{" . $emailAccount['imap_host'] . ":" . $emailAccount['imap_port'] . "/imap/ssl}";
        $encryptedPassword = EncryptionHelper::decrypt($emailAccount['password']);

        $imap = imap_open($mailbox, $emailAccount['email'], $encryptedPassword);

        if ($imap) {
            $folders = imap_list($imap, $mailbox, "*");

            if ($folders) {
                foreach ($folders as $folder) {
                    imap_reopen($imap, $folder);

                    $emails = imap_search($imap, 'ALL');
                    if ($emails) {
                        foreach ($emails as $email_number) {
                            $overview = imap_fetch_overview($imap, $email_number, 0);
                            $message_id = $overview[0]->message_id;

                            if ($this->emailModel->emailExistsByMessageId($message_id)) {
                                continue;
                            }

                            $date_received = isset($overview[0]->date) ? date('Y-m-d H:i:s', strtotime($overview[0]->date)) : null;

                            $body = imap_fetchbody($imap, $email_number, 1);

                            $subject = isset($overview[0]->subject) ? imap_utf8($overview[0]->subject) : '(no subject)';
                            $clean_subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

                            $folder_name = str_replace($mailbox, '', $folder);

                            $structure = imap_fetchstructure($imap, $email_number);
                            $content_type = $this->getContentType($structure);

                            $this->emailModel->saveEmail(
                                $emailAccount['id'], 
                                $message_id,
                                $clean_subject,
                                $overview[0]->from, 
                                $overview[0]->to, 
                                $body, 
                                imap_fetchheader($imap, $email_number), 
                                $date_received, 
                                $folder_name, 
                                $message_id, 
                                $overview[0]->references ?? null, 
                                $overview[0]->in_reply_to ?? null, 
                                $content_type 
                            );
                        }
                    }
                }
            }

            imap_close($imap);

            return ['status' => true, 'message' => 'Emails synchronized successfully'];
        } else {
            return ['status' => false, 'message' => 'Failed to connect to IMAP: ' . imap_last_error()];
        }
    }


    private function getContentType($structure) {
        $content_type = '';

        if (isset($structure->subtype)) {
            $content_type = strtolower($structure->subtype);
        }

        if (isset($structure->type)) {
            switch ($structure->type) {
                case 0:
                    $content_type = ($content_type === 'plain') ? 'text/plain' : 'text/html';
                    break;
                case 1:
                    $content_type = 'multipart/' . $content_type;
                    break;
                case 2:
                    $content_type = 'message/' . $content_type;
                    break;
                case 3:
                    $content_type = 'application/' . $content_type;
                    break;
                default:
                    $content_type = 'unknown';
                    break;
            }
        }

        return $content_type;
    }
}
