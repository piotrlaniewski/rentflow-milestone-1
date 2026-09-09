<?php

declare(strict_types=1);

namespace App\Shared\UI\Console;

use App\Shared\Application\Messaging\PublishOutbox;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:outbox:publish', description: 'Publishes pending transactional outbox messages to Kafka.')]
final class PublishOutboxCommand extends Command
{
    public function __construct(private readonly PublishOutbox $publisher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('watch', null, InputOption::VALUE_NONE, 'Keep polling until the process is stopped.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum messages claimed per batch.', '50')
            ->addOption('max-attempts', null, InputOption::VALUE_REQUIRED, 'Attempts before permanent failure.', '10')
            ->addOption('poll-ms', null, InputOption::VALUE_REQUIRED, 'Delay between empty polls in watch mode.', '1000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = self::positiveInteger($input->getOption('limit'), 'limit');
        $maxAttempts = self::positiveInteger($input->getOption('max-attempts'), 'max-attempts');
        $pollMilliseconds = self::positiveInteger($input->getOption('poll-ms'), 'poll-ms');
        $watch = (bool) $input->getOption('watch');

        do {
            $report = $this->publisher->publishBatch($limit, $maxAttempts);

            if ($report->processed() > 0) {
                $output->writeln(sprintf(
                    'Outbox: published=%d retry=%d failed=%d',
                    $report->published,
                    $report->scheduledForRetry,
                    $report->failedPermanently,
                ));
            }

            if ($watch && $report->processed() === 0) {
                usleep($pollMilliseconds * 1000);
            }
        } while ($watch);

        return Command::SUCCESS;
    }

    private static function positiveInteger(mixed $value, string $name): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($integer === false) {
            throw new \InvalidArgumentException(sprintf('Option --%s must be a positive integer.', $name));
        }

        return $integer;
    }
}
