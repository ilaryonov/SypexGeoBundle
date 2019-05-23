<?php

namespace YamilovS\SypexGeoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateDatabaseFileCommand extends Command
{
    const DATABASE_FILE_LINK = 'https://sypexgeo.net/files/SxGeoCity_utf8.zip';
    const DATABASE_FILE_NAME = 'SxGeoCity.dat';

    protected static $defaultName = 'yamilovs:sypex:update_database';

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure()
    {
        $this->setDescription('Download and extract new database file to database path');
    }

    /**
     * @param OutputInterface $output
     *
     * @return resource|null
     */
    protected function getStreamContext(OutputInterface $output)
    {
        $connection = $this->container->getParameter('yamilovs_sypex_geo.connection');
        $options = [];

        if (empty($connection)) {
            return null;
        }

        if (isset($connection['proxy'])) {
            $output->writeln('<info>Using proxy settings for connection</info>');
            $proxy = $connection['proxy'];
            $http = [];

            if (isset($proxy['host'])) {
                $http = array_merge_recursive($http, [
                    'method' => 'GET',
                    'request_fulluri' => true,
                    'timeout' => 10,
                    'proxy' => 'tcp://' . $proxy['host'],
                ]);
            }
            if (isset($proxy['auth'])) {
                $http = array_merge_recursive($http, [
                    'header' => [
                        'Proxy-Authorization: Basic ' . base64_encode($proxy['auth']),
                    ]
                ]);
            }
            $options['http'] = $http;
        }

        return stream_context_create($options);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $databasePath = $this->container->getParameter('yamilovs_sypex_geo.database_path');
        $filesystem = $this->container->get('filesystem');
        $tmpFileName = sha1(uniqid(mt_rand(), true));
        $tmpFilePath = tempnam(sys_get_temp_dir(), $tmpFileName);
        $archive = file_get_contents(self::DATABASE_FILE_LINK, false, $this->getStreamContext($output));
        $zip = new \ZipArchive();

        $io->note('Load database from ' . self::DATABASE_FILE_LINK);

        if ($archive === false) {
            $io->error('Cannot download new database file');
        } else {
            $filesystem->dumpFile($tmpFilePath, $archive);
        }

        if ($zip->open($tmpFilePath) === true) {
            $newDatabaseFile = $zip->getFromName(self::DATABASE_FILE_NAME);
            $filesystem->dumpFile($databasePath, $newDatabaseFile);
            $zip->close();
            $filesystem->remove($tmpFilePath);
            $io->note("New database file was saved to: $databasePath");
        } else {
            $io->error('Cannot open zip archive');
        }
    }
}