<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Tests\Commands;

use Imagewize\PtCli\Commands\PatternDiffCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PatternDiffCommandTest extends TestCase
{
    private PatternDiffCommand $command;
    private CommandTester $commandTester;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('pt-cli-test-');
        mkdir($this->tempDir);
        
        $application = new Application();
        $application->add(new PatternDiffCommand());
        
        $this->command = $application->find('pattern:diff');
        $this->commandTester = new CommandTester($this->command);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    private function createTempFile(string $content, string $filename = 'pattern.php'): string
    {
        $file = $this->tempDir . '/' . $filename;
        file_put_contents($file, $content);
        return $file;
    }

    // ========================================================================
    // Command Configuration Tests
    // ========================================================================

    public function testCommandHasCorrectName(): void
    {
        $this->assertSame('pattern:diff', $this->command->getName());
    }

    public function testCommandHasDescription(): void
    {
        $this->assertStringContainsString('Compare Gutenberg clipboard', $this->command->getDescription());
    }

    // ========================================================================
    // File Not Found Tests
    // ========================================================================

    public function testExecuteFailsWhenPathNotFound(): void
    {
        $this->commandTester->execute([
            'path' => '/nonexistent/path.php',
            '--from-stdin' => true,
        ]);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Path not found', $this->commandTester->getDisplay());
    }

    // ========================================================================
    // Directory Mode Tests
    // ========================================================================

    public function testExecuteScansDirectoryWithoutFromStdin(): void
    {
        $fileContent = <<<EOT
<?php
/**
 * Title: Test Pattern
 * Slug: test/pattern
 */
?>

<!-- wp:paragraph --><p><?php esc_html_e( 'Hello', 'elayne' ); ?></p><!-- /wp:paragraph -->
EOT;
        $this->createTempFile($fileContent, 'test-pattern.php');

        $this->commandTester->execute([
            'path' => $this->tempDir,
        ]);

        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('test-pattern.php', $display);
        $this->assertStringContainsString('PASSED', $display);
    }

    public function testExecuteFailsWhenSingleFileWithoutFromStdin(): void
    {
        $fileContent = '<?php /* test */ ?> <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $filePath = $this->createTempFile($fileContent);

        $this->commandTester->execute([
            'path' => $filePath,
        ]);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('For a single file, use --from-stdin', $this->commandTester->getDisplay());
    }

    // ========================================================================
    // Apply Mode Tests
    // ========================================================================

    public function testApplyModeFailsWithoutFromStdin(): void
    {
        $fileContent = '<?php /* test */ ?> <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $filePath = $this->createTempFile($fileContent);

        $this->commandTester->execute([
            'path' => $filePath,
            '--apply' => true,
        ]);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('--apply requires --from-stdin', $this->commandTester->getDisplay());
    }

    // ========================================================================
    // JSON Output Tests
    // ========================================================================

    public function testJsonOutputFormat(): void
    {
        $fileContent = '<?php /* test */ ?> <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $this->createTempFile($fileContent, 'test.php');

        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--json' => true,
        ]);

        $display = $this->commandTester->getDisplay();
        $json = json_decode($display, true);
        
        $this->assertIsArray($json);
        $this->assertArrayHasKey('test.php', $json);
        $this->assertArrayHasKey('similarity', $json['test.php']);
        $this->assertArrayHasKey('differences', $json['test.php']);
    }

    // ========================================================================
    // Similarity Threshold Tests
    // ========================================================================

    public function testSimilarityThresholdAffectsExitCode(): void
    {
        $fileContent = '<?php /* test */ ?> <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $this->createTempFile($fileContent, 'test.php');

        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--similarity-threshold' => '0.95',
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
    }

    // ========================================================================
    // Theme Option Tests
    // ========================================================================

    public function testThemeOptionAcceptsValue(): void
    {
        $fileContent = '<?php /* test */ ?> <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $this->createTempFile($fileContent, 'test.php');

        $this->commandTester->execute([
            'path' => $this->tempDir,
            '--theme' => 'elayne',
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
    }
}
