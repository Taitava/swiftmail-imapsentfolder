<?php

namespace Taitava\ImapSentFolder;

use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use Swift_Mime_Message;

class ImapSentFolderPlugin implements Swift_Events_SendListener
{
	private $mailboxes = [];
	
	/**
	 * @var null|callable
	 */
	private $call_before_saving = null;
	
	/**
	 * $mailboxes should be an array with the following structure:
	 * ```
	 * [
	 * 	'email.address@somedomain.tld' => [
	 *		'host' => 'imap.somedomain.tld',
	 * 		'port' => 993,
	 * 		'username' => 'email.address',
	 * 		'password' => 'verysecretdonotsharepubliclyintheinternet',
	 * 		'sent_folder' => 'Sent',
	 * 	],
	 *      'default' => [
	 *              'host' => 'imap.somedomain.tld',
	 *              'port' => 993,
	 *              'username' => 'other.account',
	 *              'password' => 'verysecretdonotsharepubliclyintheinternet',
	 * 		'sent_folder' => 'Sent',
	 *      ],
	 * ]
	 * ```
	 *
	 * The plugin will store the sent email messages to an IMAP account whose email address matches the email message's
	 * 'From' address (the sender). If the array does not have that particular 'From' address defined, the plugin
	 * will use the account defined with the key 'default'.
	 *
	 * ImapSentFolderPlugin constructor.
	 * @param array $mailboxes
	 */
	public function __construct(array $mailboxes)
	{
		$this->mailboxes = $mailboxes;
	}
	
	/**
	 * Invoked immediately before the Message is sent.
	 *
	 * @param Swift_Events_SendEvent $event
	 */
	public function beforeSendPerformed(Swift_Events_SendEvent $event)
	{
		// No need to do anything here
	}
	
	/**
	 * Invoked immediately after the Message is sent.
	 *
	 * @param Swift_Events_SendEvent $event
	 * @throws ImapException
	 */
	public function sendPerformed(Swift_Events_SendEvent $event)
	{
		$this->save_to_imap_folder($event->getMessage());
	}
	
	/**
	 * @param Swift_Mime_Message $email_message
	 * @throws ImapException
	 */
	private function save_to_imap_folder(Swift_Mime_Message $email_message)
	{
		if ($this->saving_allowed($email_message))
		{
			$email_content = $email_message->toString() . "\r\n";
			$from = array_keys($email_message->getFrom())[0]; // Get's an email address without the name part
			$config = $this->mailbox_config($from);
			$imap = imap_open($this->mailbox($config), $config['username'], $config['password']);
			if (false === $imap) throw new ImapException('Failed to connect to IMAP mailbox: ' . $this->mailbox($config));
			$done = imap_append($imap, $this->mailbox($config, true), $email_content, '\\Seen');
			if (!$done) throw new ImapException('Failed to save an email message to an IMAP mailbox: ' . $this->mailbox($config, true));
			imap_close($imap);
		}
	}
	
	private function saving_allowed(Swift_Mime_Message $email_message)
	{
		$callable = $this->call_before_saving;
		if (!$callable) return true; // No callback is defined, so nothing will deny sending
		$result = $callable($email_message);
		if (null === $result) return true; // Null means that the callable probably didn't have a 'return' statement.
		return (bool) $result;
	}
	
	/**
	 * @param array $config
	 * @param bool $append_folder
	 * @return string
	 */
	private function mailbox(array $config, $append_folder=false)
	{
		$folder = '';
		if ($append_folder) $folder = $config['sent_folder'];
		return sprintf('{%s:%d/imap/ssl}%s',
			$config['host'],
			(int) $config['port'],
			$folder
		);
	}
	
	/**
	 * @param string $from
	 * @return array
	 * @throws ImapException
	 */
	private function mailbox_config($from)
	{
		if (!isset($this->mailboxes[$from]))
		{
			if (isset($this->mailboxes['default']))
			{
				return (array) $this->mailboxes['default'];
			}
			else
			{
				throw new ImapException("Configuration error. Sender $from is not defined in ".__CLASS__."->mailboxes and also no default mailbox is defined.");
			}
		}
		return (array) $this->mailboxes[$from];
	}
	
	/**
	 * This van be used to declare a callback that will be called right before saving a sent message to the IMAP server.
	 * The callback gets a Swift_Mime_Message instance as it's only parameter. If the callback returns false, saving
	 * the message to the IMAP server will be cancelled. Note that returning null is NOT considered as false!
	 *
	 * @param callable $call_before_saving
	 */
	public function setCallBeforeSaving($call_before_saving)
	{
		$this->call_before_saving = $call_before_saving;
	}
}
