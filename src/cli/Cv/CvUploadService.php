<?php

namespace App\Cli\Cv;

use App\Content\ContentConfig;
use EnvPipelineSpec\Env\Env;
use App\Http\Cv\CvDataNormalizer;
use App\Http\Cv\CvRenderer;
use App\Http\Cv\CvStorage;
use App\Http\Cv\CvValidator;
use App\Http\Cv\CvViewModelBuilder;
use App\Http\Cv\LabelService;
use App\Http\Cv\RedactionService;
use App\Http\Storage\FileStorage;
use App\Http\Templating\TwigFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

final class CvUploadService
{
    private Env $env;
    private ContentConfig $content;
    private string $rootPath;
    private CvStorage $cvStorage;
    private CvValidator $validator;
    private CvRenderer $renderer;
    private CvViewModelBuilder $viewBuilder;
    private RedactionService $redactor;
    private string $labelsPath;
    private string $defaultLang;

    public function __construct(Env $env)
    {
        $this->env = $env;
        $this->rootPath = $env->rootPath();
        $this->content = new ContentConfig($this->rootPath);
        $this->cvStorage = $this->buildCvStorage();
        $this->validator = $this->buildValidator();
        $this->renderer = $this->buildRenderer();
        $this->viewBuilder = new CvViewModelBuilder();
        $this->redactor = new RedactionService();
        $this->labelsPath = $this->resolveLabelsPath();
        $this->defaultLang = $this->content->defaultLang();
    }

    public function upload(string $profile, string $jsonPath, OutputInterface $output): void
    {
        $decoded = $this->loadJson($jsonPath);
        $this->validate($decoded['raw'], $output);
        $langs = $this->content->langs();
        $primaryLang = $langs[0] ?? $this->defaultLang;

        foreach ($langs as $lang) {
            $this->renderCvForLang($profile, $lang, $primaryLang, $decoded['data'], $output);
        }
    }

    private function loadJson(string $jsonPath): array
    {
        if (!is_file($jsonPath)) {
            throw new \RuntimeException("JSON file not found: {$jsonPath}");
        }
        $content = file_get_contents($jsonPath);
        $rawData = json_decode((string) $content);
        $data = json_decode((string) $content, true);
        if (!is_array($data) || $rawData === null) {
            throw new \RuntimeException("Invalid JSON: {$jsonPath}");
        }
        return ['raw' => $rawData, 'data' => $data];
    }

    private function validate(mixed $rawData, OutputInterface $output): void
    {
        $errors = $this->validator->validate($rawData);
        if ($errors === []) {
            return;
        }
        $output->writeln('<error>Schema validation failed:</error>');
        foreach ($errors as $error) {
            $output->writeln("- {$error}");
        }
        throw new \RuntimeException('Schema validation failed.');
    }

    private function renderCvForLang(
        string $profile,
        string $lang,
        string $primaryLang,
        array $data,
        OutputInterface $output
    ): void {
        $labels = LabelService::fromJsonFile($this->labelsPath, $lang)->all();
        $normalized = $this->normalizeCvData($lang, $data);
        $this->savePrivateCv($profile, $lang, $primaryLang, $normalized, $labels);
        $this->renderPublicCvIfDefault($profile, $lang, $primaryLang, $normalized, $labels, $output);
        $output->writeln("Private CV rendered for profile {$profile} ({$lang}).");
    }

    private function normalizeCvData(string $lang, array $data): array
    {
        $normalizer = new CvDataNormalizer($lang);
        return $normalizer->normalize($data);
    }

    private function savePrivateCv(
        string $profile,
        string $lang,
        string $primaryLang,
        array $normalized,
        array $labels
    ): void {
        $privateView = $this->viewBuilder->build($normalized);
        $privateHtml = $this->renderer->renderPrivate($privateView, $labels);
        $this->cvStorage->savePrivateHtmlForLang($profile, $privateHtml, $lang);
        if ($lang === $primaryLang) {
            $this->cvStorage->savePrivateHtml($profile, $privateHtml);
        }
    }

    private function renderPublicCvIfDefault(
        string $profile,
        string $lang,
        string $primaryLang,
        array $normalized,
        array $labels,
        OutputInterface $output
    ): void {
        if (!$this->isDefaultProfile($profile)) {
            return;
        }
        $publicData = $this->redactor->redact($normalized);
        $publicView = $this->viewBuilder->build($publicData);
        $publicHtml = $this->renderer->renderPublic($publicView, $labels);
        $this->cvStorage->savePublicHtmlForLang($publicHtml, $lang);
        if ($lang === $primaryLang) {
            $this->cvStorage->savePublicHtml($publicHtml);
        }
        $output->writeln("Public CV rendered for profile {$profile} ({$lang}).");
    }

    private function resolveLabelsPath(): string
    {
        return Path::join($this->rootPath, 'src', 'resources', 'labels.json');
    }

    private function isDefaultProfile(string $profile): bool
    {
        $defaultProfile = $this->content->defaultProfile();
        return strcasecmp($profile, $defaultProfile) === 0;
    }

    private function buildCvStorage(): CvStorage
    {
        $storage = new FileStorage();
        return new CvStorage($storage, Path::join($this->rootPath, 'var', 'cache', 'html'));
    }

    private function buildValidator(): CvValidator
    {
        $schemaPath = Path::join($this->rootPath, 'schemas', 'lebenslauf.schema.json');
        return new CvValidator($schemaPath);
    }

    private function buildRenderer(): CvRenderer
    {
        $twig = TwigFactory::create(Path::join($this->rootPath, 'src', 'resources', 'templates'));
        TwigFactory::configure($twig, $this->env->basePath());
        return new CvRenderer($twig);
    }
}
