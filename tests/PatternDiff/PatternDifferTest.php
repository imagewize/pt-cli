<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Tests\PatternDiff;

use Imagewize\PtCli\Compliance\Config\ThemeConfig;
use Imagewize\PtCli\PatternDiff\PatternDiffer;
use PHPUnit\Framework\TestCase;

class PatternDifferTest extends TestCase
{
    private PatternDiffer $differ;
    private ThemeConfig $config;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('pt-cli-test-');
        mkdir($this->tempDir);
        
        $configData = [
            'themeSlug' => 'test-theme',
            'patternPrefix' => 'test/',
            'woocommerce' => ['enabled' => false],
        ];
        $this->config = new ThemeConfig($configData);
        $this->differ = new PatternDiffer($this->config);
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

    public function testDiffClipboardWithFileReturnsArray(): void
    {
        $fileContent = '<?php /* test */ ?> <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $filePath = $this->createTempFile($fileContent);
        $clipboard = '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        
        $result = $this->differ->diffClipboardWithFile($clipboard, $filePath);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('differences', $result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('similarity', $result);
    }

    public function testDiffFilesReturnsHighSimilarityForIdenticalFiles(): void
    {
        $fileContent = '<?php /* test */ ?> <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        
        $file1Path = $this->createTempFile($fileContent, 'file1.php');
        $file2Path = $this->createTempFile($fileContent, 'file2.php');
        
        $result = $this->differ->diffFiles($file1Path, $file2Path);
        
        $this->assertSame(1.0, $result['similarity']);
    }

    public function testExtractBlocksFromPhpHandlesNoHeader(): void
    {
        $content = '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $blocks = $this->differ->extractBlocksFromPhp($content);
        
        $this->assertSame($content, $blocks);
    }

    public function testIsExemptReturnsTrueForWooCommercePluginPatterns(): void
    {
        $path = '/path/to/wp-content/plugins/woocommerce/patterns/test.php';
        $this->assertTrue($this->differ->isExempt($path));
    }

    public function testIsExemptReturnsFalseForThemePatterns(): void
    {
        $path = '/path/to/theme/patterns/test.php';
        $this->assertFalse($this->differ->isExempt($path));
    }
}
