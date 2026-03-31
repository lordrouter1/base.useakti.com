<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the akti_load_env() helper function.
 */
class EnvLoaderTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'env_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        // Clean up test env vars
        foreach (['TEST_VAR_A', 'TEST_VAR_B', 'TEST_VAR_C', 'TEST_VAR_D', 'TEST_VAR_E', 'TEST_VAR_EXPORT', 'TEST_INLINE_COMMENT'] as $var) {
            putenv($var);
            unset($_ENV[$var], $_SERVER[$var]);
        }
    }

    /** @test */
    public function loads_simple_key_value_pairs(): void
    {
        file_put_contents($this->tmpFile, "TEST_VAR_A=hello\nTEST_VAR_B=world\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('hello', getenv('TEST_VAR_A'));
        $this->assertSame('world', getenv('TEST_VAR_B'));
    }

    /** @test */
    public function skips_comments_and_empty_lines(): void
    {
        file_put_contents($this->tmpFile, "# This is a comment\n\nTEST_VAR_A=value\n# Another comment\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('value', getenv('TEST_VAR_A'));
    }

    /** @test */
    public function handles_double_quoted_values(): void
    {
        file_put_contents($this->tmpFile, 'TEST_VAR_A="hello world"' . "\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('hello world', getenv('TEST_VAR_A'));
    }

    /** @test */
    public function handles_single_quoted_values(): void
    {
        file_put_contents($this->tmpFile, "TEST_VAR_A='hello world'\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('hello world', getenv('TEST_VAR_A'));
    }

    /** @test */
    public function handles_escape_sequences_in_double_quotes(): void
    {
        file_put_contents($this->tmpFile, 'TEST_VAR_A="line1\nline2"' . "\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame("line1\nline2", getenv('TEST_VAR_A'));
    }

    /** @test */
    public function strips_inline_comments_from_unquoted_values(): void
    {
        file_put_contents($this->tmpFile, "TEST_INLINE_COMMENT=value # this is a comment\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('value', getenv('TEST_INLINE_COMMENT'));
    }

    /** @test */
    public function handles_export_prefix(): void
    {
        file_put_contents($this->tmpFile, "export TEST_VAR_EXPORT=exported_value\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('exported_value', getenv('TEST_VAR_EXPORT'));
    }

    /** @test */
    public function does_not_override_existing_env_vars(): void
    {
        putenv('TEST_VAR_A=original');
        file_put_contents($this->tmpFile, "TEST_VAR_A=overridden\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('original', getenv('TEST_VAR_A'));
    }

    /** @test */
    public function silently_skips_nonexistent_file(): void
    {
        // Should not throw or error
        \akti_load_env('/nonexistent/path/.env');
        $this->assertTrue(true);
    }

    /** @test */
    public function sets_env_server_superglobals(): void
    {
        file_put_contents($this->tmpFile, "TEST_VAR_C=superglobal\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('superglobal', $_ENV['TEST_VAR_C']);
        $this->assertSame('superglobal', $_SERVER['TEST_VAR_C']);
    }

    /** @test */
    public function handles_empty_value(): void
    {
        file_put_contents($this->tmpFile, "TEST_VAR_D=\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('', getenv('TEST_VAR_D'));
    }

    /** @test */
    public function handles_special_characters_in_quoted_password(): void
    {
        file_put_contents($this->tmpFile, 'TEST_VAR_E="kP9!vR2@mX6#zL5$"' . "\n");
        \akti_load_env($this->tmpFile);

        $this->assertSame('kP9!vR2@mX6#zL5$', getenv('TEST_VAR_E'));
    }
}
