<?php
require_once __DIR__ . '/../helper.php';

use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
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
}
