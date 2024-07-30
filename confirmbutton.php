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
    }

    private function confirmItem()
    {
        $input = Factory::getApplication()->input;
        $id = $input->getInt('id');
        $email = $input->getString('email');
        $fullName = $input->getString('fullname');
        $table = $input->getString('tbl');

        if ($id && $email && $fullName && $table)
        {
            $db = Factory::getDbo();

            // Check if records are over 1000
            $query = $db->getQuery(true)
                        ->select('COUNT(*)')
                        ->from($db->quoteName($table));
            $db->setQuery($query);
            $count = $db->loadResult();

            if ($count > 1000) {
                // Set status as 'waiting list'
                $status = 'waiting list';
                $messageBody = "Dear $fullName,

Thank you for registering for the 2nd Indonesia-Africa Forum (IAF).
Due to overwhelming interest, we have reached our full capacity, and your
registration is now on the waiting list.
To follow up on the spot availability, please reach out to us by email at iaf.reg@kemlu.go.id.

Warm regards,
The 2nd IAF Registration Committee";
            } else {
                // Set status as 'approved'
                $status = 'approved';
                $messageBody = "Dear $fullName,

Thank you for registering for the 2nd Indonesia-Africa Forum (IAF). Your participation is confirmed for the event in Bali scheduled from September 1-3, 2024.

Please visit our website at iaf.kemlu.go.id for the latest updates on the program, concept note, administrative details, and more. Please be advised that some programs require special access to attend.

Should you require assistance with visa procedures, don't hesitate to get in touch with the Indonesian Embassy in your country.

For any further questions, feel free to email us at iaf.reg@kemlu.go.id.

We are looking forward to welcoming you to Bali.

Warm regards,
The 2nd IAF Registration Committee";
            }

            $query = $db->getQuery(true)
                        ->update($db->quoteName($table))
                        ->set($db->quoteName('status') . ' = ' . $db->quote($status))
                        ->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $db->execute();

            // Send confirmation email
            $mailer = Factory::getMailer();
            $config = Factory::getConfig();

            $sender = array(
                $config->get('mailfrom'),
                $config->get('fromname')
            );

            $mailer->setSender($sender);
            $mailer->addRecipient($email);
            $mailer->setSubject('Confirmation');
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

                $messageBody = "Dear $fullName,

We regret to inform you that your status for the 2nd Indonesia-Africa Forum (IAF) has been rescinded. Please contact us at iaf.reg@kemlu.go.id if you have any questions or need further assistance.

Warm regards,
The 2nd IAF Registration Committee";

                $mailer = Factory::getMailer();
                $config = Factory::getConfig();

                $sender = array(
                    $config->get('mailfrom'),
                    $config->get('fromname')
                );

                $mailer->setSender($sender);
                $mailer->addRecipient($email);
                $mailer->setSubject('Status Rescinded');
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
}
?>