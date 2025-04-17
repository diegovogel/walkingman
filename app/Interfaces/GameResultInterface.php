<?php

namespace App\Interfaces;

interface GameResultInterface
{
    /**
     * Calculate the score for this game result based on its properties.
     */
    public function calculateScore(): int;
}
