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
        elseif ($task === 'rescindRejectItem')
        {
            $this->rescindRejectItem();
        }
    }

    private function confirmItem()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $email = $input->getString('email');
        $fullName = $input->getString('fullname');
        $table = $input->getString('tbl');
        $quota = $input->getInt('quota', $this->params->get('quota', 9999)); // Get quota from params

        if ($id && $email && $fullName && $table)
        {
            $db = Factory::getDbo();

            // Check if records are over the quota
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
                ['{fullname}'],
                [$fullName],
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
            $mailer->setSubject($this->params->get('confirmation_subject', 'The Second IAF Registration'));
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
            Factory::getApplication()->enqueueMessage('Missing ID, email, fullname, or table name', 'error');
        }

        $app = Factory::getApplication();
        $app->redirect(JRoute::_(Factory::getURI()->toString(), false));
    }

    private function rescindItem()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $table = $input->getString('tbl');

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
                        ->select($db->quoteName(array('email', 'fullname')))
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $result = $db->loadObject();

            if ($result) {
                $email = $result->email;
                $fullName = $result->fullname;

                $messageTemplate = $this->params->get('rescinded_message', 'Default rescinded message');

                // Replace placeholders with actual values
                $messageBody = str_replace(
                    ['{fullname}'],
                    [$fullName],
                    $messageTemplate
                );

                $mailer = Factory::getMailer();
                $config = Factory::getConfig();

                $sender = array(
                    $config->get('mailfrom'),
                    $config->get('fromname')
                );

                $mailer->setSender($sender);
                $mailer->addRecipient($email);
                $mailer->setSubject($this->params->get('rescinded_subject', 'The Second IAF Registration'));
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
        $fullName = $input->getString('fullname');
        $table = $input->getString('tbl');

        if ($id && $email && $fullName && $table)
        {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                        ->update($db->quoteName($table))
                        ->set($db->quoteName('status') . ' = ' . $db->quote('rejected'))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $db->execute();

            // Replace placeholders with actual values
            $messageTemplate = $this->params->get('rejected_message', 'Default rejected message');
            $messageBody = str_replace(
                ['{fullname}'],
                [$fullName],
                $messageTemplate
            );

            // Send rejection email
            $mailer = Factory::getMailer();
            $config = Factory::getConfig();

            $sender = array(
                $config->get('mailfrom'),
                $config->get('fromname')
            );

            $mailer->setSender($sender);
            $mailer->addRecipient($email);
            $mailer->setSubject($this->params->get('rejection_subject', 'The Second IAF Registration'));
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
            Factory::getApplication()->enqueueMessage('Missing ID, email, fullname, or table name', 'error');
        }

        $app = Factory::getApplication();
        $app->redirect(JRoute::_(Factory::getURI()->toString(), false));
    }

    private function rescindRejectItem()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $email = $input->getString('email');
        $fullName = $input->getString('fullname');
        $table = $input->getString('tbl');

        if ($id && $email && $fullName && $table)
        {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                        ->update($db->quoteName($table))
                        ->set($db->quoteName('status') . ' = ' . $db->quote('pending'))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $db->execute();

            // Replace placeholders with actual values
            $messageTemplate = $this->params->get('rescind_reject_message', 'Default rescind reject message');
            $messageBody = str_replace(
                ['{fullname}'],
                [$fullName],
                $messageTemplate
            );

            // Send rescind reject email
            $mailer = Factory::getMailer();
            $config = Factory::getConfig();

            $sender = array(
                $config->get('mailfrom'),
                $config->get('fromname')
            );

            $mailer->setSender($sender);
            $mailer->addRecipient($email);
            $mailer->setSubject($this->params->get('rescind_reject_subject', 'The Second IAF Registration'));
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
            Factory::getApplication()->enqueueMessage('Missing ID, email, fullname, or table name', 'error');
        }

        $app = Factory::getApplication();
        $app->redirect(JRoute::_(Factory::getURI()->toString(), false));
    }
}
?>