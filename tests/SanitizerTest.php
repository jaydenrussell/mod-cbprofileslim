<?php
require_once __DIR__ . '/../helper.php';

use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    protected function setUp(): void
    {
        // Provide a site host so sanitizeAvatarUrl()'s same-site absolute-URL
        // branch has a reference host in the test environment.
        $_SERVER['HTTP_HOST'] = 'simcoecurlingclub.ca';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST']);
    }
    // ---- Avatar sanitizer (Critical fix regression) ----
    public function avatarProvider()
    {
        return [
            'flat filename'            => ['normal.jpg', '/images/comprofiler/normal.jpg'],
            'cb-style filename'        => ['383_abc.jpg', '/images/comprofiler/383_abc.jpg'],
            'protocol-relative'        => ['//evil.com/x', ''],
            'http scheme'              => ['http://evil.com/x', ''],
            'javascript scheme'        => ['javascript:alert(1)', ''],
            'path traversal'           => ['../../etc/passwd', ''],
            'subdirectory'             => ['sub/dir/x.png', ''],
            'space in name'            => ['a b.jpg', ''],
            'backslash'                => ['c:\\x', ''],
            'empty'                    => ['', ''],
            'same-site absolute'       => ['https://simcoecurlingclub.ca/images/comprofiler/383_abc.jpg', '/images/comprofiler/383_abc.jpg'],
            'same-site absolute sub'   => ['https://simcoecurlingclub.ca/images/comprofiler/gallery/x.png', '/images/comprofiler/gallery/x.png'],
            'foreign absolute'         => ['https://evil.com/images/comprofiler/x.jpg', ''],
        ];
    }

    /** @dataProvider avatarProvider */
    public function testSanitizeAvatarUrl($input, $expected)
    {
        $this->assertSame($expected, ModCbProfileSlimHelper::sanitizeAvatarUrl($input));
    }

    // ---- Profile URL validator (MEDIUM fix regression) ----
    public function urlProvider()
    {
        return [
            'https default'   => ['https://simcoecurlingclub.ca/scc-profile', 'https://simcoecurlingclub.ca/scc-profile'],
            'http ok'         => ['http://example.com/a', 'http://example.com/a'],
            'javascript'      => ['javascript:alert(1)', ''],
            'data uri'        => ['data:text/html,alert(1)', ''],
            'protocol-rel'    => ['//evil.com', ''],
            'uppercase https' => ['HTTPS://x.com', 'HTTPS://x.com'],
            'quote breakout'  => ['https://x.com"onclick=1', ''],
            'empty'           => ['', ''],
        ];
    }

    /** @dataProvider urlProvider */
    public function testValidateUrl($input, $expected)
    {
        $this->assertSame($expected, ModCbProfileSlimHelper::validateUrl($input));
    }

    // ---- CSS validator (MEDIUM fix regression) ----
    public function cssProvider()
    {
        return [
            'default pad'     => ['0 0 0 0', '0 0 0 0'],
            'zero'            => ['0', '0'],
            'two values'      => ['10px 5px', '10px 5px'],
            'css injection'   => ['0} body{display:none}/*', ''],
            'semicolon'       => ['0;color:red', ''],
            'url()'           => ['url(http://x)', ''],
            'rem unit'        => ['1.5rem', '1.5rem'],
            'empty'           => ['', ''],
        ];
    }

    /** @dataProvider cssProvider */
    public function testValidateCss($input, $expected)
    {
        $this->assertSame($expected, ModCbProfileSlimHelper::validateCss($input));
    }

    // ---- Base path validator (H1 regression: reject traversal / empty segments) ----
    public function basePathProvider()
    {
        return [
            'default'          => ['/images/comprofiler/', '/images/comprofiler/'],
            'subdir'           => ['/images/comprofiler/gallery/', '/images/comprofiler/gallery/'],
            'traversal'        => ['/images/../secret/', '/images/comprofiler/'],
            'traversal mid'    => ['/images/comprofiler/../x/', '/images/comprofiler/'],
            'double slash'     => ['/images//comprofiler/', '/images/comprofiler/'],
            'scheme'           => ['https://evil.com/x/', '/images/comprofiler/'],
            'empty'            => ['', '/images/comprofiler/'],
        ];
    }

    /** @dataProvider basePathProvider */
    public function testValidateBasePath($input, $expected)
    {
        $this->assertSame($expected, ModCbProfileSlimHelper::validateBasePath($input));
    }

    // ---- Avatar absolute same-site URL (needs a known site host in env) ----
    public function testSanitizeSameSiteAbsoluteUrl()
    {
        $_SERVER['HTTP_HOST'] = 'simcoecurlingclub.ca';
        $this->assertSame(
            '/images/comprofiler/383_abc.jpg',
            ModCbProfileSlimHelper::sanitizeAvatarUrl('https://simcoecurlingclub.ca/images/comprofiler/383_abc.jpg')
        );
        // foreign host must be rejected
        $this->assertSame(
            '',
            ModCbProfileSlimHelper::sanitizeAvatarUrl('https://evil.com/images/comprofiler/x.jpg')
        );
        unset($_SERVER['HTTP_HOST']);
    }
}
