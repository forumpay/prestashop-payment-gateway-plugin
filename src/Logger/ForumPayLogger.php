<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Logger;

use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiErrorException;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\InvalidApiResponseException;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\InvalidResponseException;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\InvalidResponseJsonException;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\InvalidResponseStatusCodeException;
use Psr\Log\LoggerInterface;

class ForumPayLogger implements LoggerInterface
{
    private const LOG_LEVEL_INFORMATIVE = 1;
    private const LOG_LEVEL_WARNING = 2;
    private const LOG_LEVEL_ERROR = 3;
    private const LOG_LEVEL_CRASH = 4;

    /**
     * @var string
     */
    private string $prefix;

    /**
     * @var ParserInterface[]
     */
    private array $parsers;

    /**
     * If enabled, logs debug level. Default is false.
     *
     * @var bool
     */
    private bool $logDebug;

    /**
     * Constructor
     *
     * @param string $prefix
     */
    public function __construct(string $prefix = 'ForumPayWebApi')
    {
        $this->prefix = $prefix;
        $this->logDebug = false;
    }

    /**
     * Data parsers are added using this method.
     *
     * @param ParserInterface $parser
     * @return $this
     */
    public function addParser(ParserInterface $parser): self
    {
        $this->parsers[] = $parser;

        return $this;
    }

    /**
     * Sets debug level.
     *
     * @param bool $debug
     *
     * @return $this
     */
    public function setLogDebug(bool $debug): self
    {
        $this->logDebug = $debug;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_CRASH, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_CRASH, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_INFORMATIVE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_INFORMATIVE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = []): void
    {
        $this->log(self::LOG_LEVEL_INFORMATIVE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        if ($level === self::LOG_LEVEL_INFORMATIVE && $this->logDebug === false) {
            return;
        }

        foreach ($this->parsers as $parser) {
            $context = $parser->parse(['access_token', 'stats_token'], $context);
        }

        \PrestaShopLogger::addLog(
            pSQL(sprintf(
                '%s %s',
                $this->formatLogMessage($message),
                $this->formatContext($context)
            )), $level);
    }

    /**
     * @param ApiExceptionInterface $e
     *
     * @return void
     */
    public function logApiException(ApiExceptionInterface $e): void
    {
        $pos = strrpos(get_class($e), '\\');
        $exceptionClass = $pos === false ? get_class($e) : substr(get_class($e), $pos + 1);

        switch ($e) {
            case $e instanceof ApiErrorException || $e instanceof InvalidResponseJsonException:
                $this->error(
                    $this->formatApiExceptionMessage($e, $exceptionClass),
                    ['parameters' => $e->getCallParameters(), 'trace' => $e->getTrace()]
                );
                break;

            case $e instanceof InvalidApiResponseException:
                $this->error(
                    $this->formatApiExceptionMessage($e, $exceptionClass),
                    [
                        'curlInfo' => $e->getCurlInfo(),
                        'parameters' => $e->getCallParameters(),
                        'trace' => $e->getTrace(),
                    ]
                );
                break;

            case $e instanceof InvalidResponseException:
                $this->error(
                    $this->formatInvalidResponseExceptionMessage($e, $exceptionClass),
                    [
                        'response' => $e->getResponse(),
                        'parameters' => $e->getCallParameters(),
                        'trace' => $e->getTrace(),
                    ]
                );
                break;

            case $e instanceof InvalidResponseStatusCodeException:
                $this->error(
                    $this->formatInvalidResponseStatusCodeExceptionMessage($e, $exceptionClass),
                    ['parameters' => $e->getCallParameters(), 'trace' => $e->getTrace()]
                );
                break;
        }
    }

    /**
     * Method for formatting instances of ApiExceptionInterface that
     * do not have any additional properties apart from those in
     * ForumPay\PaymentGateway\PHPClient\Http\Exception\AbstractApiException
     *
     * @param ApiExceptionInterface $e
     * @param string $exceptionClass
     *
     * @return string
     */
    private function formatApiExceptionMessage(ApiExceptionInterface $e, string $exceptionClass): string
    {
        return sprintf(
            '%s: %s %s, Message: %s',
            $exceptionClass,
            $e->getHttpMethod(),
            $e->getUri(),
            $e->getMessage()
        );
    }

    /**
     * Method for formatting InvalidResponseException
     *
     * @param InvalidResponseException $e
     * @param string $exceptionClass
     *
     * @return string
     */
    private function formatInvalidResponseExceptionMessage(InvalidResponseException $e, string $exceptionClass): string
    {
        return sprintf(
            '%s: %s %s, Message: %s, Action: %s',
            $exceptionClass,
            $e->getHttpMethod(),
            $e->getUri(),
            $e->getMessage(),
            $e->getAction(),
        );
    }

    /**
     * Method for formatting InvalidResponseStatusCodeException
     *
     * @param InvalidResponseStatusCodeException $e
     * @param string $exceptionClass
     *
     * @return string
     */
    private function formatInvalidResponseStatusCodeExceptionMessage(
        InvalidResponseStatusCodeException $e,
        string $exceptionClass
    ): string {
        return sprintf(
            '%s: %s %s, Status Code: %s, Message: %s',
            $exceptionClass,
            $e->getHttpMethod(),
            $e->getUri(),
            $e->getResponseStatusCode(),
            $e->getMessage(),
        );
    }

    /**
     * Return default formatted message
     *
     * @param string $message
     *
     * @return string
     */
    private function formatLogMessage(string $message): string
    {
        return sprintf('%s - %s', $this->prefix, $message);
    }

    /**
     * Return formatted context
     *
     * @param array $context
     *
     * @return string
     */
    private function formatContext(array $context): string
    {
        return sprintf('Context = %s', json_encode($context));
    }
}
