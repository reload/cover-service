<?php

namespace App\Service\VendorService;

use App\Utils\Message\VendorImportResultMessage;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Interface VendorServiceImporterInterface.
 *
 * All import vendors should implement this interface to ensure they are discovered by the system.
 */
interface VendorServiceImporterInterface extends VendorServiceInterface
{
    public const BATCH_SIZE = 200;
    public const ERROR_RUNNING = 'Could not require locks - import may already be running';

    /**
     * Loading data from the vendor for processing.
     *
     * @return VendorImportResultMessage
     *  To hold vendor import success information
     */
    public function load(): VendorImportResultMessage;

    /**
     * Set limit.
     *
     * Limit the amount of records imported by the vendor (mostly for testing purpose)
     *
     * @param int $limit
     *   The number of records to limit the vendor to import
     */
    public function setLimit(int $limit = 0);

    /**
     * Sets the without-queue option.
     *
     * Should the imported data be sent into the queues and processed (mostly for testing purpose)
     *
     * @param bool $withoutQueue
     *   If ture the records found are not parsed into the queue system
     */
    public function setWithoutQueue(bool $withoutQueue = false);

    /**
     * Set the vendor to run updates on existing known covers from earlier imports.
     *
     *  Date to limit the time back in time to preform updates
     */
    public function setWithUpdatesDate(\DateTime $date);

    /**
     * Set force/ignore locks.
     *
     * @param bool $force
     *   Default locks are not ignored
     */
    public function setIgnoreLock(bool $force = false);

    /**
     * Set progress bar.
     *
     * @param ProgressBar $progressBar
     *   The progress bar to use
     */
    public function setProgressBar(ProgressBar $progressBar): void;
}
