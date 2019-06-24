<?php

namespace YamilovS\SypexGeoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use YamilovS\SypexGeoBundle\Manager\SypexGeoManager;

class GetIpDataCommand extends Command
{
    /** @var SypexGeoManager */
    protected $sypexGeoManager;

    public function __construct(SypexGeoManager $sypexGeoManager)
    {
        parent::__construct();

        $this->sypexGeoManager = $sypexGeoManager;
    }

    protected function configure() {
        $this
            ->setName('yamilovs:sypex-geo:get-ip-data')
            ->setDescription('Get all data about specific ip from database file')
            ->addArgument('ip', InputArgument::REQUIRED, 'IP address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $ip = $input->getArgument('ip');
        $city_data = $this->sypexGeoManager->getCity($ip);

        $io->title("Data from database file for address $ip");
        $headers = ['Parent', 'Parameter' ,'Value'];
        $rows = [];

        foreach ($city_data as $key => $value) {
            foreach ($value as $k => $v) {
                $rows[] = [$key, $k, $v];
            }
        }

        $io->table($headers, $rows);
    }
}