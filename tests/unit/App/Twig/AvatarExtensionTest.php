<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use PHPUnit\Framework\TestCase;

class AvatarExtensionTest extends TestCase
{
    public function testIsGettingAvatar(): void
    {
        $extension = new AvatarExtension('//gravatar.com/avatar');
        $avatar = $extension->getAvatar('foo@bar.com');
        $this->assertEquals('//gravatar.com/avatar/f3ada405ce890b6f8204094deb12d8a8?s=60', $avatar);
    }

    public function testIsGettingAvatarWithCustomConfig(): void
    {
        $extension = new AvatarExtension('//gravatar.com/avatar', ['a' => 'b']);
        $avatar = $extension->getAvatar('foo@bar.com');
        $this->assertEquals('//gravatar.com/avatar/f3ada405ce890b6f8204094deb12d8a8?s=60&a=b', $avatar);
    }

    public function testIsNotGettingAvatarWithoutEmail(): void
    {
        $extension = new AvatarExtension('//gravatar.com/avatar');
        $avatar = $extension->getAvatar('');
        $this->assertEmpty($avatar);
    }
}
