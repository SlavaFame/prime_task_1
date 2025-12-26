<?php

namespace Company\CatalogRest\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Company\CatalogRest\General\ExcelExporter;

class ExportProductsCommand extends Command
{
    protected static $defaultName = 'catalog:export-products';
    protected static $defaultDescription = 'Экспорт товаров в Excel и отправка на email';

    protected function configure(): void
    {
        $this
            ->setDescription('Выгрузка товаров каталога в Excel')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'Email для отправки файла'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Лимит товаров (0 - без ограничений)',
                0
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Инициализация Bitrix
        define('NO_KEEP_STATISTIC', true);
        define('NOT_CHECK_PERMISSIONS', true);

        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

        // Проверка email
        $email = $input->getArgument('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>Неверный формат email</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Начало выгрузки товаров...</info>');

        try {
            $exporter = new ExcelExporter();
            $result = $exporter->exportToEmail($email);

            if ($result) {
                $output->writeln('<info>Выгрузка успешно завершена. Файл отправлен на ' . $email . '</info>');
                return Command::SUCCESS;
            } else {
                $output->writeln('<error>Ошибка при выгрузке</error>');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}