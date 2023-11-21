<?php
/**
 * @file
 * Trait adding progressbar support
 */

namespace App\Service;

use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Trait ProgressBarTrait.
 */
trait ProgressBarTrait
{
    private ProgressBar $progressBar;

    /**
     * Set the progressbar to write to.
     *
     * @param ProgressBar $progressBar
     */
    public function setProgressBar(ProgressBar $progressBar): void
    {
        $this->progressBar = $progressBar;
    }

    /**
     * Start progress.
     *
     * @param string $message
     */
    private function progressStart(string $message): void
    {
        $this->progressMessage($message);
        $this->progressBar->start();
        $this->progressBar->advance();
    }

    /**
     * Advance progress.
     */
    private function progressAdvance(): void
    {
        $this->progressBar->advance();
    }

    /**
     * Set progress bar message.
     *
     * @param string $message
     */
    private function progressMessage(string $message): void
    {
        $this->progressBar->setMessage($message);
    }

    /**
     * Finish progress.
     */
    private function progressFinish(): void
    {
        $this->progressBar->finish();
    }
}
