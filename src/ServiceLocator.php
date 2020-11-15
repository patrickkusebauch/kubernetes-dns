<?php

declare(strict_types = 1);

namespace PatrickKusebauch\KubernetesDNS;

final class ServiceLocator
{
    public const SOURCE_DNS = 'dns';
    public const SOURCE_ENV = 'env';

    public const DEFAULT_CONFIG = [
        'preferredSource' => self::SOURCE_DNS,
        'allowedSources'  => [self::SOURCE_DNS, self::SOURCE_ENV,],
    ];

    /** @var array{
     *  preferredSource: ?string,
     *  allowedSources: array<string>
     * }
     */
    private $config;

    /** @param  array{
     *  preferredSource?: ?string,
     *  allowedSources?: array<string>
     * } $config
     *
     * @throws \InvalidArgumentException if preferred source is not allowed or unrecognized sources are used
     */
    public function __construct(array $config = [])
    {
        $completeConfig = array_merge(self::DEFAULT_CONFIG, $config);
        if ($completeConfig['preferredSource'] !== null
            && in_array(
                   $completeConfig['preferredSource'],
                   $completeConfig['allowedSources'],
                   true
               ) === false
        ) {
            self::throwNotAllowedPreferred($completeConfig['preferredSource'], $completeConfig['allowedSources']);
        }

        $unrecognizedSources = array_diff($completeConfig['allowedSources'], [self::SOURCE_DNS, self::SOURCE_ENV]);
        if ($unrecognizedSources !== []) {
            throw new \InvalidArgumentException(
                sprintf('Unrecognized sources "%s" used.', implode(', ', $unrecognizedSources))
            );
        }

        $this->config = $completeConfig;
    }

    /**
     * @param  array<string>  $allowedSources
     * @return no-return
     * @throws \InvalidArgumentException that the preferred source is not allowed
     */
    private static function throwNotAllowedPreferred(string $preferredSource, array $allowedSources): void
    {
        throw new \InvalidArgumentException(
            sprintf(
                'The preferred source "%s" is not one of the allowed sources. (Allowed: %s)',
                $preferredSource,
                implode(
                    ', ',
                    $allowedSources
                )
            )
        );
    }

    /**
     * @param  array<string>  $allowedSources
     * @throws \InvalidArgumentException when the preferred source is no longer allowed
     */
    public function changeAllowedSources(array $allowedSources): self
    {
        if ($this->config['preferredSource'] !== null
            && in_array(
                   $this->config['preferredSource'],
                   $allowedSources,
                   true
               ) === false
        ) {
            self::throwNotAllowedPreferred($this->config['preferredSource'], $allowedSources);
        }

        $this->config['allowedSources'] = $allowedSources;
        return $this;
    }

    /**
     * @throws \InvalidArgumentException when the preferred source is not allowed
     */
    public function changePreferredSource(?string $preferredSource): self
    {
        if ($preferredSource === null) {
            $this->config['preferredSource'] = null;
            return $this;
        }

        if (in_array($preferredSource, $this->config['allowedSources'], true) === false) {
            self::throwNotAllowedPreferred($preferredSource, $this->config['allowedSources']);
        }

        $this->config['preferredSource'] = $preferredSource;
        return $this;
    }

    /**
     * @param  string  $serviceName  Name of the service you are looking for
     * @param  string|null  $source  Source of the host information (null=default defined at construction)
     * @return ?string hostname, null when not found
     * @throws \InvalidArgumentException If the source is not allowed or not recognized.
     */
    public function serviceHost(string $serviceName, ?string $source = null): ?string
    {
        if ($source === null) {
            if ($this->config['preferredSource'] === null) {
                throw new \InvalidArgumentException(
                    'You have to define a source, when there is no default source set.'
                );
            }
            $source = $this->config['preferredSource'];
        }
        if (in_array($source, $this->config['allowedSources'], true) === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The source "%s" is not one of the allowed sources. (Allowed: %s)',
                    $source,
                    implode(
                        ', ',
                        $this->config['allowedSources']
                    )
                )
            );
        }

        if ($source === self::SOURCE_DNS) {
            $hostName = gethostbyname($serviceName);
            return $hostName !== $serviceName ? $hostName : null;
        }

        if ($source === self::SOURCE_ENV) {
            $hostName = getenv(self::translateServiceNameToEnvName($serviceName));
            return $hostName !== false ? $hostName : null;
        }

        throw new \InvalidArgumentException(sprintf('Unrecognized source "%s".', $source));
    }

    private static function translateServiceNameToEnvName(string $serviceName): string
    {
        return str_replace('-', '_', strtoupper($serviceName)).'_SERVICE_HOST';
    }
}