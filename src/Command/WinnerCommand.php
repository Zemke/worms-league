<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\ReplayData;

#[AsCommand(
    name: 'app:winner',
    description: 'Determine the winner from a WA replay extraced JSON file.',
)]
class WinnerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'f',
                InputArgument::REQUIRED,
                'WA replay extracted log and then to JSON processed file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fp = $input->getArgument('f');
        $f = fopen($fp, 'r') or die('Unable to open file');
        if ($fp) {
            $io->note(sprintf('Reading file: %s', $fp));
        }
        $r = fread($f, filesize($fp));
        $d = json_decode($r, true);
        $rd = (new ReplayData())->setData($d);
        $w = $rd->winner();
        if (is_null($w)) {
            $io->success('The round has drawn.');
        } else {
            $io->success('The round was won by ' . $w);
        }
        return Command::SUCCESS;
    }
}
