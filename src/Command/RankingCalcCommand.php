<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\RankingService;
use App\Repository\SeasonRepository;
use App\Entity\Season;

#[AsCommand(
    name: 'app:ranking:calc',
    description: 'Force re-calc of ranking for currently active season.',
)]
class RankingCalcCommand extends Command
{
    public function __construct(private RankingService $rankingService,
                                private SeasonRepository $seasonRepo,)
    {
        parent::__construct();
    }


    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $season = $this->seasonRepo->findActive();
        if (is_null($season)) {
            throw RuntimeException('There is no active season');
        }

        $this->rankingService->reCalc($season);

        $io = new SymfonyStyle($input, $output);
        $io->success('Done');

        return Command::SUCCESS;
    }
}
