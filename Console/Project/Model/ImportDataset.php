<?php

namespace Console\Project\Model;

use Console\Project\Model;
use FragTale\Service\Cli;
use FragTale\DataCollection;
use FragTale\Application\Model\Dataset;
use FragTale\Service\Project\CliPurpose;

class ImportDataset extends Model {
	/**
	 *
	 * @var CliPurpose
	 */
	protected CliPurpose $ProjectService;

	/**
	 *
	 * {@inheritdoc}
	 * @see \Console\Project\Model::executeOnTop()
	 */
	protected function executeOnTop(): void {
		if ($this->getProjectName ())
			$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Insert or update your custom dataset "%s"' ), $this->getProjectName () ), Cli::COLOR_YELLOW );

		$this->CliService->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN )
			->printInColor ( dgettext ( 'core', 'This controller automatically upserts data into specified table defined from your model.' ), Cli::COLOR_LCYAN )
			->printInColor ( '	' . dgettext ( 'core', 'An entity folder must contain a file prefixed by "D_" and containing specified data to import into the database.' ), Cli::COLOR_CYAN )
			->printInColor ( '	' . dgettext ( 'core', 'An array $definition must have been set into the "Dataset" class in the constructor. Definition is what this process import.' ), Cli::COLOR_CYAN )
			->printInColor ( dgettext ( 'core', '**********************' ), Cli::COLOR_LCYAN );
	}

	/**
	 * Instructions executed only if application is launched via CLI
	 */
	protected function executeOnConsole(): void {
		if ($this->isHelpInvoked ())
			return;

		if (! $this->getSuperServices ()->getLocalizeService ()->meansYes ( $this->CliService->prompt ( dgettext ( 'core', 'Confirm dataset import from your project models into your database: [yN]' ) ) )) {
			$this->CliService->printWarning ( dgettext ( 'core', 'Process interrupted' ) );
			return;
		}

		$this->ProjectService = $this->getService ( CliPurpose::class )->setName ( $this->getProjectName () );

		# Scan model's folder to find D_ classes: dataset to import
		// Get model's folder
		$modelsFolder = $this->getModelFolder ();
		foreach ( scandir ( $modelsFolder ) as $model ) {
			if (in_array ( $model, [ 
					'.',
					'..'
			] ))
				continue;
			// Define namespace
			$modelNamespace = $this->getModelNamespace ( $model );
			$modelFolder = "$modelsFolder/$model";

			foreach ( scandir ( $modelFolder ) as $entity ) {
				$entitydir = "$modelFolder/$entity";
				if (in_array ( $entitydir, [ 
						'.',
						'..'
				] ) && is_dir ( $entitydir ))
					continue;
				foreach ( scandir ( $entitydir ) as $filename ) {
					if (strpos ( $filename, 'D_' ) === 0) {
						$filepath = "$entitydir/$filename";
						include $filepath;
						$entityNamespace = "$modelNamespace\\$entity";
						$datasetClassname = "$entityNamespace\\D_$entity";
						$Dataset = new $datasetClassname ();
						if ($Dataset instanceof Dataset) {
							$entityClassname = "$entityNamespace\\E_$entity";
							$this->CliService->printInColor ( sprintf ( dgettext ( 'core', 'Working on Dataset %s' ), $datasetClassname ), $this->CliService::COLOR_YELLOW );
							foreach ( $Dataset->getDefinition () as $Row ) {
								$status = $this->upsertDataset ( new $entityClassname (), $Row );
								if ($status === \FragTale\Application\Model::STATUS_ERROR)
									$color = $this->CliService::COLOR_RED;
								else
									$color = $this->CliService::COLOR_CYAN;
								$this->CliService->printInColor ( dgettext ( 'core', 'Result: ' ) . $status, $color );
							}
						} else {
							// Error
							$this->CliService->printError ( sprintf ( dgettext ( 'core', 'Class %s is not an instance of Dataset. It cannot be used to import definition.' ), $datasetClassname ) );
						}
					}
				}
			}
		}
	}

	/**
	 *
	 * @param \FragTale\Application\Model $Entity
	 * @param DataCollection $UpsertedRow
	 * @return string|NULL
	 */
	protected function upsertDataset(\FragTale\Application\Model $Entity, DataCollection $UpsertedRow): ?string {
		$this->CliService->print ( dgettext ( 'core', 'Upserting: ' ) . $UpsertedRow->toJsonString ( false ) );
		return $Entity->upsertDb ( $UpsertedRow )->getLastTransactionStatus ();
	}
}