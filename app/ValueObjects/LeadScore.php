<?php

namespace App\ValueObjects;

/**
 * A Value Object to represent a lead's score.
 * This ensures that the score is always a positive integer and provides a consistent
 * way to handle scoring logic throughout the application.
 */
final class LeadScore
{
    private int $score;

    public function __construct(int $score)
    {
        if ($score < 0) {
            throw new \InvalidArgumentException('Lead score cannot be negative.');
        }
        $this->score = $score;
    }

    public function getValue(): int
    {
        return $this->score;
    }

    public function equals(self $other): bool
    {
        return $this->score === $other->score;
    }

    public function __toString(): string
    {
        return (string) $this->score;
    }
}
