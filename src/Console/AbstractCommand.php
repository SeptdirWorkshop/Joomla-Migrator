<?php
/*
 * @package     RadicalMart Package
 * @subpackage  com_radicalmart
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\System\Migrator\Console;

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Console\Command\AbstractCommand as BaseCommand;
use Joomla\Plugin\System\Migrator\Traits\Commands\UtilitiesTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends BaseCommand
{
	use UtilitiesTrait;

	/**
	 * The output to command style.
	 *
	 * @var   SymfonyStyle
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected SymfonyStyle $ioStyle;

	/**
	 * The command line input.
	 *
	 * @var   InputInterface
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected InputInterface $input;

	/**
	 * The command line output.
	 *
	 * @var   OutputInterface
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected OutputInterface $output;

	/**
	 * The input to inject into the command.
	 *
	 * @var   InputInterface
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected InputInterface $cliInput;

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandText = '';

	/**
	 * Command description for configure help block.
	 *
	 * @var   string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $commandDescription = '';

	/**
	 * Command aliases.
	 *
	 * @var string[]
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $commandAliases = [];

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected array $methods = [];

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @throws \Exception
	 *
	 * @return  integer  The command exit code.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->input  = $input;
		$this->output = $output;

		// Configure command
		$this->configureSymfonyIO($input, $output);
		$this->configureUri();
		$this->configureInput($input);

		$io = $this->ioStyle;
		try
		{
			if (empty($this->methods))
			{
				throw new \Exception('No method to run', 0);
			}

			foreach ($this->methods as $method)
			{
				if (is_string($method) && !method_exists($this, $method))
				{
					throw new \Exception('Method `' . $method . '` not found');
				}
				elseif (!is_string($method) && !is_callable($method))
				{
					throw new \Exception('Method `' . $method . '` not callable');
				}
				if (is_string($method))
				{
					call_user_func_array([$this, $method], [$input, $output]);
				}
				else
				{
					$method($this->ioStyle, $this->cliInput);
				}
			}
		}
		catch (\Throwable $e)
		{
			try
			{
				$io->progressFinish();
			}
			catch (\Throwable)
			{

			}

			$this->onError($e);

			$io->error($e->getCode() . ': ' . $e->getMessage());
			if (JDEBUG)
			{
				$this->writeExceptionTrace($e);
			}
		}

		return 0;
	}

	/**
	 * Configure the command.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function configure(): void
	{
		if (!empty($this->commandText))
		{
			$this->setDescription($this->commandText);
		}

		$this->configureOptions();

		$help = [];
		if (!empty($this->commandDescription))
		{
			$help[] = '<info>%command.name%</info> ' . $this->commandDescription;
		}

		$help[] = 'Usage: <info>php %command.full_name% [flags]</info>';

		$this->setHelp(implode(PHP_EOL, $help));

		$this->setAliases($this->commandAliases);
	}

	/**
	 * Configure options.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function configureOptions(): void
	{
	}

	/**
	 * Configure the IO.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function configureSymfonyIO(InputInterface $input, OutputInterface $output): void
	{
		$this->cliInput = $input;
		$this->ioStyle  = new SymfonyStyle($input, $output);
	}

	/**
	 * Configure inputs.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function configureInput(InputInterface $input)
	{
	}

	/**
	 * Method to correct configure URI if live site option don't empty.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function configureUri(): void
	{
		if (empty($this->cliInput->getOption('live-site')))
		{
			return;
		}

		$this->uriReinitialize(true);
	}

	/**
	 * Run on command error.
	 *
	 * @param   \Exception  $e  Exception object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function onError(\Throwable $e)
	{
	}

	/**
	 * Method to write Exception Trace.
	 *
	 * @param   \Throwable|null  $e  Throwable object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function writeExceptionTrace(?\Throwable $e): void
	{
		if ($e === null)
		{
			return;
		}

		$backtraces = [];
		while ((!empty($e)))
		{
			$backtrace = $e->getTrace();
			array_unshift($backtrace, [
				'file'     => $e->getFile(),
				'line'     => $e->getLine(),
				'function' => '()']);
			$backtraces[] = $backtrace;

			$e = $e->getPrevious();
		}

		if (empty($backtraces))
		{
			return;
		}

		foreach ($backtraces as $backtraceList)
		{
			$table = [];
			foreach ($backtraceList as $k => $backtrace)
			{
				$table[] = [
					'i'        => $k + 1,
					'function' => (isset($backtrace['class']))
						? $backtrace['class'] . $backtrace['type'] . $backtrace['function'] . '()'
						: $backtrace['function'],
					'location' => (isset($backtrace['file'])) ?
						HTMLHelper::_('debug.xdebuglink', $backtrace['file'], $backtrace['line']) : '',
				];
			}

			$this->ioStyle->table(['#', 'Location', 'Function'], $table);
		}
	}
}