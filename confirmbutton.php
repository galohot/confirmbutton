<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailHelper;

class PlgSystemConfirmButton extends CMSPlugin
{
    protected $app;

    public function onAfterRoute()
    {
        $input = Factory::getApplication()->input;
        $task = $input->getCmd('task');

        if ($task === 'confirmItem')
        {
            $this->confirmItem();
        }
        elseif ($task === 'rescindItem')
        {
            $this->rescindItem();
        }
        elseif ($task === 'rejectItem')
        {
            $this->rejectItem();
        }
        elseif ($task === 'rescindReject')
        {
            $this->rescindReject();
        }
    }

    private function confirmItem()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $email = $input->getString('email');
        $firstName = $input->getString('first_name');
        $lastName = $input->getString('last_name');
        $table = $input->getString('tbl');
        $quota = $input->getInt('quota', 9999);

        if ($id && $email && $firstName && $lastName && $table)
        {
            $db = Factory::getDbo();

            // Check if records are over quota
            $query = $db->getQuery(true)
                        ->select('COUNT(*)')
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('status') . ' = ' . $db->quote('approved'));
            $db->setQuery($query);
            $count = $db->loadResult();

            if ($count > $quota) {
                // Set status as 'waiting list'
                $status = 'waiting list';
                $messageTemplate = $this->params->get('waiting_list_message', 'Default waiting list message');
            } else {
                // Set status as 'approved'
                $status = 'approved';
                $messageTemplate = $this->params->get('approved_message', 'Default approved message');
            }

            $query = $db->getQuery(true)
                        ->update($db->quoteName($table))
                        ->set($db->quoteName('status') . ' = ' . $db->quote($status))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $db->execute();

            // Replace placeholders with actual values
            $messageBody = str_replace(
                ['{first_name}', '{last_name}'],
                [$firstName, $lastName],
                $messageTemplate
            );

            // Send confirmation email
            $mailer = Factory::getMailer();
            $config = Factory::getConfig();

            $sender = array(
                $config->get('mailfrom'),
                $config->get('fromname')
            );

            $mailer->setSender($sender);
            $mailer->addRecipient($email);
            $mailer->setSubject($this->params->get('email_subject', 'The Second IAF Registration'));
            $mailer->setBody($messageBody);

            if ($mailer->Send() !== true)
            {
                Factory::getApplication()->enqueueMessage('Error sending email', 'error');
            }
            else
            {
                Factory::getApplication()->enqueueMessage('Status updated and email sent', 'message');
            }
        }
        else
        {
            Factory::getApplication()->enqueueMessage('Missing ID, email, first name, last name, or table name', 'error');
        }

        $app = Factory::getApplication();
        $app->redirect(JRoute::_(Factory::getURI()->toString(), false));
    }

    private function rescindItem()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $table = $input->getString('tbl');
        $reason = $input->getString('reason', '');

        if ($id && $table)
        {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                        ->update($db->quoteName($table))
                        ->set($db->quoteName('status') . ' = ' . $db->quote('rescinded'))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $db->execute();

            // Send rescind email
            $query = $db->getQuery(true)
                        ->select($db->quoteName(array('email', 'first_name', 'last_name')))
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $result = $db->loadObject();

            if ($result) {
                $email = $result->email;
                $firstName = $result->first_name;
                $lastName = $result->last_name;

                $messageTemplate = $this->params->get('rescinded_message', 'Default rescinded message');

                // Replace placeholders with actual values
                $messageBody = str_replace(
                    ['{first_name}', '{last_name}'],
                    [$firstName, $lastName],
                    $messageTemplate
                );

                if (!empty($reason)) {
                    $messageBody .= "\n\nP.S.\nThe reasons are as follows:\n" . $reason;
                }

                $mailer = Factory::getMailer();
                $config = Factory::getConfig();

                $sender = array(
                    $config->get('mailfrom'),
                    $config->get('fromname')
                );

                $mailer->setSender($sender);
                $mailer->addRecipient($email);
                $mailer->setSubject($this->params->get('email_subject', 'The Second IAF Registration'));
                $mailer->setBody($messageBody);

                if ($mailer->Send() !== true)
                {
                    Factory::getApplication()->enqueueMessage('Error sending rescind email', 'error');
                }
                else
                {
                    Factory::getApplication()->enqueueMessage('Status rescinded and email sent', 'message');
                }
            }
        }
        else
        {
            Factory::getApplication()->enqueueMessage('Missing ID or table name', 'error');
        }

        $app = Factory::getApplication();
        $app->redirect(JRoute::_(Factory::getURI()->toString(), false));
    }

    private function rejectItem()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $email = $input->getString('email');
        $firstName = $input->getString('first_name');
        $lastName = $input->getString('last_name');
        $table = $input->getString('tbl');
        $reason = $input->getString('reason', '');

        if ($id && $email && $firstName && $lastName && $table)
        {
            $db = Factory::getDbo();

            $query = $db->getQuery(true)
                        ->update($db->quoteName($table))
                        ->set($db->quoteName('status') . ' = ' . $db->quote('rejected'))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $db->execute();

            $messageTemplate = $this->params->get('rejected_message', 'Default rejected message');

            // Replace placeholders with actual values
            $messageBody = str_replace(
                ['{first_name}', '{last_name}'],
                [$firstName, $lastName],
                $messageTemplate
            );

            if (!empty($reason)) {
                $messageBody .= "\n\nP.S.\nThe reasons are as follows:\n" . $reason;
            }

            // Send rejection email
            $mailer = Factory::getMailer();
            $config = Factory::getConfig();

            $sender = array(
                $config->get('mailfrom'),
                $config->get('fromname')
            );

            $mailer->setSender($sender);
            $mailer->addRecipient($email);
            $mailer->setSubject($this->params->get('email_subject', 'The Second IAF Registration'));
            $mailer->setBody($messageBody);

            if ($mailer->Send() !== true)
            {
                Factory::getApplication()->enqueueMessage('Error sending rejection email', 'error');
            }
            else
            {
                Factory::getApplication()->enqueueMessage('Status updated and email sent', 'message');
            }
        }
        else
        {
            Factory::getApplication()->enqueueMessage('Missing ID, email, first name, last name, or table name', 'error');
        }

        $app = Factory::getApplication();
        $app->redirect(JRoute::_(Factory::getURI()->toString(), false));
    }

    private function rescindReject()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $table = $input->getString('tbl');
        $reason = $input->getString('reason', '');

        if ($id && $table)
        {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                        ->update($db->quoteName($table))
                        ->set($db->quoteName('status') . ' = ' . $db->quote('pending'))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $db->execute();

            // Send rescind rejection email
            $query = $db->getQuery(true)
                        ->select($db->quoteName(array('email', 'first_name', 'last_name')))
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $result = $db->loadObject();

            if ($result) {
                $email = $result->email;
                $firstName = $result->first_name;
                $lastName = $result->last_name;

                $messageTemplate = $this->params->get('rescind_rejection_message', 'Default rescind rejection message');

                // Replace placeholders with actual values
                $messageBody = str_replace(
                    ['{first_name}', '{last_name}'],
                    [$firstName, $lastName],
                    $messageTemplate
                );

                if (!empty($reason)) {
                    $messageBody .= "\n\nP.S.\nThe reasons are as follows:\n" . $reason;
                }

                $mailer = Factory::getMailer();
                $config = Factory::getConfig();

                $sender = array(
                    $config->get('mailfrom'),
                    $config->get('fromname')
                );

                $mailer->setSender($sender);
                $mailer->addRecipient($email);
                $mailer->setSubject($this->params->get('email_subject', 'The Second IAF Registration'));
                $mailer->setBody($messageBody);

                if ($mailer->Send() !== true)
                {
                    Factory::getApplication()->enqueueMessage('Error sending rescind rejection email', 'error');
                }
                else
                {
                    Factory::getApplication()->enqueueMessage('Status rescinded and email sent', 'message');
                }
            }
        }
        else
        {
            Factory::getApplication()->enqueueMessage('Missing ID or table name', 'error');
        }

        $app = Factory::getApplication();
        $app->redirect(JRoute::_(Factory::getURI()->toString(), false));
    }
}
?>