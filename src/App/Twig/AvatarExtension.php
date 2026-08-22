<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use Twig\Attribute\AsTwigFunction;

class AvatarExtension
{
    /**
     * @param array<string, string|int> $avatarConfig
     */
    public function __construct(protected string $avatarUrl, protected array $avatarConfig = [])
    {
    }

    #[AsTwigFunction('getAvatar')]
    public function getAvatar(?string $email, int $size = 60): string
    {
        if (!$email) {
            return '';
        }

        $queryString = array_merge(['s' => $size], $this->avatarConfig);

        return sprintf('%s/%s?%s', $this->avatarUrl, md5(strtolower($email)), http_build_query($queryString));
    }
}
