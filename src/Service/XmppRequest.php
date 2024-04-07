<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Job;
use LogicException;

final readonly class XmppRequest
{
    private const string AuthTypePlain = 'PLAIN';
    private const string AuthTypeDigestMd5 = 'DIGEST-MD5';
    const int PRIORITY_UPPER_BOUND = 127;
    const int PRIORITY_LOWER_BOUND = -128;

    public static function startTls(): string
    {
        return "<starttls xmlns=\"urn:ietf:params:xml:ns:xmpp-tls\"/>";
    }

    public static function setResource(string $resource): string
    {
        $id = uniqid();

        return "<iq type=\"set\" id=\"$id\"><bind xmlns=\"urn:ietf:params:xml:ns:xmpp-bind\"><resource>$resource</resource></bind></iq>";
    }

    public static function openXmlStream(string $host): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><stream:stream xmlns=\"jabber:client\" to=\"$host\" xmlns:stream=\"http://etherx.jabber.org/streams\" version=\"1.0\">";
    }

    public static function closeXmlStream(): string
    {
        return '</stream:stream>';
    }

    public static function generateAuthXml(
        string $authType,
        string $username,
        string $password,
    ): string {
        $cred = self::encodedCredentials(
            username: $username,
            password: $password,
            authType: $authType
        );

        return "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='$authType'>$cred</auth>";
    }

    public static function encodedCredentials(
        string $username,
        string $password,
        string $authType,
    ): string {
        $key = "\x00$username\x00$password";

        $result = match ($authType) {
            self::AuthTypePlain => base64_encode($key),
            self::AuthTypeDigestMd5 => sha1($key),
            default => throw new LogicException('Wrong auth type'),
        };

        return htmlspecialchars($result, ENT_XML1, 'utf-8');
    }

    public static function setPresence(
        string $from,
        string $to,
        string $type
    ): string {
        return "<presence from='$from' to='$to' type='$type'/>";
    }

    public static function setPriority(
        string $username,
        string $host,
        string $resource,
        int $value,
        ?string $forResource = null
    ): string {
        $from = htmlspecialchars(
            "$username@$host/$resource",
            ENT_XML1,
            'utf-8'
        );
        if ($forResource) {
            $from = $username."/$forResource";
        }

        if ($value > self::PRIORITY_UPPER_BOUND) {
            $value = self::PRIORITY_UPPER_BOUND;
        } elseif ($value < self::PRIORITY_LOWER_BOUND) {
            $value = self::PRIORITY_LOWER_BOUND;
        }

        return "<presence from='$from'><priority>$value</priority></presence>";
    }

    public static function addToRoster(
        string $name,
        string $forJid,
        string $from,
        ?string $groupName = null
    ): string {
        $id = uniqid();
        $group = null;
        if ($groupName) {
            $group = "<group>$groupName</group>";
        }

        return "<iq type='set' id='$id' from='$from'><query xmlns='jabber:iq:roster'><item jid='$forJid' name='$name'>$group</item></query></iq>";
    }

    public static function removeFromRoster(string $jid): string
    {
        $id = uniqid();

        return "<iq type='set' id='$id'><query xmlns='jabber:iq:roster'><item jid='$jid' subscription='remove'/></query></iq>";
    }

    public static function setGroup(string $name, string $forJid): string
    {
        $id = uniqid();

        return "<iq type='set' id='$id'><query xmlns='jabber:iq:roster'><item jid='$forJid'><group>$name</group></item></query></iq>";
    }

    public static function getRoster(Job $job): string
    {
        return "<iq type='get' id='{$job->getId()}'><query xmlns='jabber:iq:roster'/></iq>";
    }

    public static function getServerVersion(): string
    {
        $id = uniqid();

        return "<iq type='get' id='$id'><query xmlns='jabber:iq:version'/></iq>";
    }

    public static function getServerTime(): string
    {
        $id = uniqid();

        return "<iq type='get' id='$id'><query xmlns='urn:xmpp:time'/></iq>";
    }

    public static function getChats(Job $job): string
    {
        return "<iq type='get' id='{$job->getId()}'><query xmlns='jabber:iq:private'><storage xmlns='storage:bookmarks'/></query></iq>";
    }

    public static function getFeatures(string $forJid): string
    {
        $id = uniqid();

        return "<iq type='get' to='$forJid' id='$id'><query xmlns='http://jabber.org/protocol/disco#info'></query></iq>";
    }

    public static function ping(Job $job): string
    {
        return "<iq type='get' id='{$job->getId()}'><ping xmlns='urn:xmpp:ping'/></iq>";
    }

    public static function presence(): string
    {
        return '<presence/>';
    }

    /**
     * @param string $body
     * @param string $to
     * @param "chat"|"groupchat" $type
     * @return string
     */
    public static function message(
        string $body,
        string $to,
        string $type
    ): string {
        $to = htmlspecialchars($to, ENT_XML1, 'utf-8');
        $body = htmlspecialchars($body, ENT_XML1, 'utf-8');

        return "<message to='$to' type='$type'><body>$body</body></message>";
    }

    public static function mute(string $to, string $nick): string
    {
        $id = uniqid();

        return "<iq id='$id' to='$to' type='set'><query xmlns='http://jabber.org/protocol/muc#admin'><item nick='$nick' role='visitor'/></query></iq>";
    }

    public static function subscribe(
        Job $job,
        string $jid,
        string $username,
    ): string {
        return "<presence id='{$job->getId()}' to='$jid/$username'/>";
    }

    public static function unsubscribe(
        string $username,
        string $host,
        string $from,
    ): string {
        return self::setPresence(
            from: "$username@$host",
            to: $from,
            type: 'unsubscribe'
        );
    }

    public static function acceptSubscription(
        string $username,
        string $host,
        string $from,
    ): string {
        return self::setPresence(
            from: "$username@$host",
            to: $from,
            type: 'subscribed'
        );
    }

    public static function declineSubscription(
        string $username,
        string $host,
        string $from,
    ): string {
        return self::setPresence(
            from: "$username@$host",
            to: $from,
            type: 'unsubscribed'
        );
    }

    public static function listSubscriptions(): string
    {
        $id = uniqid();

        return "<iq type='get' id='$id'><pubsub xmlns='http://jabber.org/protocol/pubsub'><subscriptions/></pubsub></iq>";
    }
}
