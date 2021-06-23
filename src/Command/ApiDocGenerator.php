<?php

namespace Dev\ApiDocBundle\Command;

use Dev\ApiDocBundle\Describer\OperationDescriber;
use Dev\ApiDocBundle\Locator\Locator;
use Dev\ApiDocBundle\Registry\ComponentRegistry;
use Dev\ApiDocBundle\Registry\OperationRegistry;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
use function array_merge;

#[AsCommand(
    name: 'api-doc:generate'
)]
class ApiDocGenerator extends AbstractCommand
{
    public function __construct(
        private KernelInterface $kernel,
        private OperationDescriber $operationDescriber,
        private OperationRegistry $operationRegistry,
        private ComponentRegistry $componentRegistry
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption('src', null, InputOption::VALUE_OPTIONAL, 'Source code directory, default <project_dir>/src.')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output yaml file, default <project_dir>/doc.yaml.')
            ->addOption('proto', null, InputOption::VALUE_OPTIONAL, 'Proto file which will be included in output, default <project_dir>/proto.yaml.')
            ->addOption('title', null, InputOption::VALUE_OPTIONAL)
            ->addOption('doc-version', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $title = $input->getOption('title') ?: 'API Documentation';
        $version = $input->getOption('doc-version') ?: '1.0.0';
        $src = $input->getOption('src') ?: $this->kernel->getProjectDir().'/src';
        $output = $input->getOption('output') ?: $this->kernel->getProjectDir().'/'.'doc.yaml';

        if (!is_dir($src)) {
            $this->io->error('Passed src is invalid');

            return Command::FAILURE;
        }
        $proto = $input->getOption('proto') ?: $this->kernel->getProjectDir().'/proto.yaml';

        $locator = new Locator();
        $classes = $locator->locate($src);

        $describer = $this->operationDescriber;

        foreach ($classes as $class) {
            $describer->describe($class);
        }

        $protoData = [];
        if (file_exists($proto)) {
            try {
                $protoData = Yaml::parseFile($proto);
            } catch (Exception $exception) {
                $this->io->error('Error with proto file parsing: '.$exception->getMessage());

                return Command::FAILURE;
            }
        }

        $data = $this->getData($this->operationRegistry, $this->componentRegistry, $protoData, $version, $title);

        $this->write($data, $output);

        $this->io->success('done');

        return 0;
    }

    private function write(array $data, string $file): void
    {
        $inline = 10;
        $yaml = Yaml::dump($data, $inline);
        file_put_contents($file, $yaml);
    }

    private function getData(
        OperationRegistry $operationRegistry,
        ComponentRegistry $componentRegistry,
        array $protoData = [],
        string $version = '1.0.0',
        string $title = 'API Documentation'
    ): array
    {
        $paths = $operationRegistry->toArray($protoData);
        $components = $componentRegistry->toArray();

        $securitySchemes = $protoData['components']['securitySchemes'] ?? [];
        $components = array_merge($components, ['securitySchemes' => $securitySchemes]);

        $schemas = $protoData['components']['schemas'] ?? [];
        $components['schemas'] = array_merge($components['schemas'] ?? [], $schemas);

        $components['responses'] = $protoData['components']['responses'] ?? [];

        return [
            'openapi' => '3.0.1',
            'info' => [
                'version' => $version,
                'title' => $title,
            ],
            'paths' => $paths,
            'components' => $components,
        ];
    }
}