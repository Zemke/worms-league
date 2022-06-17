<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\SendReplayMessage;
use App\Repository\GameRepository;

#[AsCommand(
    name: 'app:game:process',
    description: 'Force re-calc of ranking for currently active season.',
)]
class ProcessGameCommand extends Command
{
    public function __construct(private MessageBusInterface $bus,
                                private GameRepository $gameRepo,)
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->addArgument('gameId', InputArgument::REQUIRED, 'The ID of game whose replays to process.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $game = $this->gameRepo->find(intval($input->getArgument('gameId')));
        foreach ($game->getReplays() as $replay) {
            $io->info('Dispatching replay ' . $replay->getId());
            $this->bus->dispatch(new SendReplayMessage($replay->getId()));
        }
        $io->success('Done');
        return Command::SUCCESS;
    }
}

