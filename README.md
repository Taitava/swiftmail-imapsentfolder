# swiftmailer-imapsentfolder

This is a plugin for [SwiftMailer](https://github.com/swiftmailer/swiftmailer) that stores all* sent email messages to an IMAP server (or servers).

*) Optionally you can use a callback function to create a custom filter that can prevent saving messages if needed.

**This feature only works with IMAP servers, any other protocols are not supported!** It does not matter, which protocol was originally used to send the message.

## Requirements
- SwiftMailer 6
- PHP imap extension

## Installation

`composer require taitava/swiftmailer-imapsentfolder`

## Usage

```php
use \Taitava\ImapSentFolder\ImapSentFolderPlugin;
$mailer = Swift_Mailer::newInstance();

// Define mailboxes like this:
$mailboxes = [
     'email.address@somedomain.tld' => [
             'host' => 'imap.somedomain.tld',
             'port' => 993,
             'username' => 'email.address',
             'password' => 'verysecretdonotsharepubliclyintheinternet',
        'sent_folder' => 'Sent',
     ],
     'default' => [
             'host' => 'imap.somedomain.tld',
             'port' => 993,
             'username' => 'other.account',
             'password' => 'verysecretdonotsharepubliclyintheinternet',
        'sent_folder' => 'Sent',
     ],
];

$plugin = new ImapSentFolderPlugin($mailboxes);

$mailer->registerPlugin($plugin);
```

The plugin will automatically pick the correct mailbox by inspecting the 'From' field from the email message that was sent. If the email address is not found from the `$mailboxes` array, the plugin will use the mailbox defined with the key 'default'. You should **always define a default mailbox** if its possible that mail is sent from unforeseen email addresses!

## Control what gets saved and what not

You can define a custom callback function that will be called right before saving a sent email message.

```php
use \Taitava\ImapSentFolder\ImapSentFolderPlugin;
$plugin = new ImapSentFolderPlugin($mailboxes);

$plugin->setCallBeforeSaving(function (Swift_Mime_Message $email_message){
        // ... Inspect the $email_message instance ...

        // ... Decide not to save this message ...
        return false;

        // ... Decide to accept saving the message ...
        return true;

        // ... If you do not write a 'return' statement or if you return null, saving is also accepted ...
        return;
});
```

## Future

Ideas (and pull requests) are welcome :). No big plans at the moment, I'm considering this plugin quite complete. But will try to fix issues if any arise.

## Author

Oh, it's just me. Too lazy to write my name. :)

## License

MIT
