<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AvatarExtension extends AbstractExtension
{
    public function __construct(protected string $avatarUrl, protected array $avatarConfig = [])
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getAvatar', [$this, 'getAvatar']),
        ];
    }

    public function getAvatar($email, $size = 60): string
    {
        if (!$email) {
            return '';
        }

        $queryString = array_merge(['s' => $size], $this->avatarConfig);

        return sprintf('%s/%s?%s', $this->avatarUrl, md5(strtolower($email)), http_build_query($queryString));
    }
}
