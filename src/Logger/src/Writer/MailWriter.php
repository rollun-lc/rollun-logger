<?php


namespace rollun\logger\Writer;

use Traversable;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;


/**
 * Class MailWriter
 * @package rollun\logger\Writer
 */
class MailWriter extends AbstractWriter
{
    /**
     * @var mixed|null
     */
    private $email;
    /**
     * @var null
     */
    private $pass;
    /**
     * @var null
     */
    private $toEmails;


    /**
     * MailWriter constructor.
     * @param $options
     * @param null $email
     * @param null $pass
     * @param null $toEmails
     */
    public function __construct($options, $email = null, $pass = null, $toEmails = null)
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }
        if (is_array($options)) {
            parent::__construct($options);
            $email = $options['name'];
            $pass = $options['pass'];
            $toEmails = $options['toEmails'];
        };

        $this->email = $email;
        $this->pass = $pass;
        $this->toEmails = $toEmails;
    }

    /**
     * Write a message to the log
     *
     * @param array $event log data event
     * @return void
     */
    protected function doWrite(array $event)
    {

        $parts = [];

        $message = $event['message'];
        $textPart = new MimePart($message);
        $textPart->type = 'text/plain';
        $parts[] = $textPart;

        if (isset($event['context']['html'])) {
            $htmlPart = new MimePart($event['context']['html']);
            $htmlPart->type = 'text/html';
            $parts[] = $htmlPart;
        }

        $images = $event['context']['png'] ?? [];
        foreach ($images as $image) {
            $tmpFileName = tempnam(sys_get_temp_dir(), 'image');
            file_put_contents($tmpFileName, base64_decode($image));
            $imagePart = new MimePart(fopen($tmpFileName, 'r'));
            $imagePart->type = 'image/png';
            $imagePart->filename = 'ScreenShoot.png';
            $imagePart->disposition = Mime::DISPOSITION_ATTACHMENT;
            $imagePart->encoding = Mime::ENCODING_BASE64;
            $parts[] = $imagePart;
        }

        // Setup SMTP transport using PLAIN authentication
        $transport = new SmtpTransport();
        $options = new SmtpOptions(array(
            'name' => 'gmail',
            'host' => 'smtp.gmail.com',
            'port' => '465',
            'connection_class' => 'plain',
            'connection_config' => array(
                'username' => $this->email,
                'password' => $this->pass,
                'ssl' => 'ssl',
            ),
        ));
        $transport->setOptions($options);

        $message = new Message();

        $message->setSubject('Log writer');
        $message->addFrom($this->email);

        foreach ($this->toEmails as $email) {
            $message->addTo($email);
        }

        $body = new MimeMessage();
        $body->setParts($parts);
        $message->setBody($body);

        $contentTypeHeader = $message->getHeaders()->get('Content-Type');
        $contentTypeHeader->setType('multipart/related');

        $transport->send($message);


    }
}