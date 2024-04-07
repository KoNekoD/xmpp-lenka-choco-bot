<?php

namespace App\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class JabberClient
{
    /** @var resource $conn */
    private $conn;

    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'xmpp.host')] private readonly string $host,
        #[Autowire(param: 'xmpp.username')] private readonly string $username,
        #[Autowire(param: 'xmpp.password')] private readonly string $password,
        #[Autowire(param: 'xmpp.resource')] private string $resource,
        #[Autowire(param: 'xmpp.port')] private readonly int $port,
        #[Autowire(param: 'xmpp.use_tls')] private readonly bool $useTls,
        #[Autowire(param: 'xmpp.auth_type')] private readonly string $authType
    ) {}

    public function send(string $xml, bool $noWaitAnswer = false): string
    {
        try {
            fwrite($this->conn, $xml);
            $this->logger->info("send: $xml");
            if (socket_get_status($this->conn)['eof']) {
                $this->logger->warning("fwrite() Probably a broken pipe");
            }
        } catch (Exception $e) {
            $this->logger->error('fwrite() failed '.$e->getMessage());

            return '';
        }
        if ($noWaitAnswer) {
            return '';
        }

        return $this->receive();
    }

    public function receive(): string
    {
        $response = '';
        while ($out = fgets($this->conn)) {
            $response .= $out;
        }
        $response = $this->checkForErrors($response);
        if (!$response) {
            $this->logger->info('receive fail');

            return '';
        }

        $this->logger->info('receive', ['resp' => $response]);

        return $response;
    }

    private function checkForErrors(string $response): string
    {
        preg_match_all(
            "#<stream:error>(<(.*?) (.*?)\/>)<\/stream:error>#",
            $response,
            $streamErrors
        );
        if ((!empty($streamErrors[0])) && count($streamErrors[2]) > 0) {
            $this->logger->error($streamErrors[2][0], ['resp' => $response]);
            $this->reconnect();
            $response = '';
        }

        return $response;
    }

    public function reconnect(?string $resourcePostfix = null): void
    {
        if ($resourcePostfix) {
            $this->resource = $this->resource.'_'.$resourcePostfix;
        }

        $this->disconnect();
        $socket = stream_socket_client(
            sprintf(
                'tcp://j.%s:%d',
                $this->host,
                $this->port
            )
        );
        if ($socket === false) {
            throw new Exception("Failed to connect");
        }

        $ms = 250000; // 150000

        stream_set_timeout($socket, 0, $ms);
        $this->conn = $socket;

        $this->connect();
    }

    public function disconnect(): string
    {
        if (!is_resource($this->conn)) {
            return '';
        }

        $this->send(XmppRequest::closeXmlStream());

        $received = $this->receive();
        $this->logger->warning('disconnect: '.$received);

        fclose($this->conn);

        return $received;
    }

    private function connect(): void
    {
        $response = $this->send(
            XmppRequest::openXmlStream($this->host)
        );

        $this->logger->info('openXmlStream', ['resp' => $response]);

        $isTlsSupported = self::isTlsSupported($response);
        $isTlsRequired = self::isTlsRequired($response);

        $this->logger->info(
            'isTlsSupported: ',
            ['isTlsSupported' => $isTlsSupported]
        );
        $this->logger->info(
            'isTlsRequired: ',
            ['isTlsRequired' => $isTlsRequired]
        );

        if ($isTlsSupported && ($isTlsRequired || $this->useTls)) {
            $response = $this->send(XmppRequest::startTls());
            if (!self::canProceed($response)) {
                $this->logger->error(
                    'startTls: TLS authentication failed',
                    ['resp' => $response]
                );

                return;
            }

            stream_socket_enable_crypto(
                $this->conn,
                true,
                STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
            );
            $this->send(XmppRequest::openXmlStream($this->host));
        }
        $this->send(
            XmppRequest::generateAuthXml(
                authType: $this->authType,
                username: $this->username,
                password: $this->password
            )
        );
        $this->send(XmppRequest::openXmlStream($this->host));
        if (trim($this->resource)) {
            $this->send(
                XmppRequest::setResource($this->resource),
                true
            );
        }

        $this->send(XmppRequest::presence(), true);
    }

    public static function isTlsSupported(string $xml): bool
    {
        $matchTag = self::matchCompleteTag($xml, "starttls");

        return $matchTag !== '';
    }

    public static function matchCompleteTag(string $xml, string $tag): string
    {
        $match = self::matchTag($xml, $tag);

        if (count($match) > 0) {
            return $match[0];
        }

        return '';
    }

    /** @return string[] */
    private static function matchTag(string $xml, string $tag): array
    {
        $tpl = "#<$tag.*?>(.*)<\/$tag>#";
        preg_match($tpl, $xml, $match);

        if (count($match) < 1) {
            return [];
        }

        return $match;
    }

    public static function isTlsRequired(string $xml): bool
    {
        if (!self::isTlsSupported($xml)) {
            return false;
        }
        $tls = self::matchCompleteTag($xml, "starttls");
        preg_match("#required#", $tls, $match);

        return count($match) > 0;
    }

    public static function canProceed(string $xml): bool
    {
        $tpl =
            "#<proceed xmlns=[\'|\"]urn:ietf:params:xml:ns:xmpp-tls[\'|\"]\/>#";
        preg_match($tpl, $xml, $match);

        return count($match) > 0;
    }
}
