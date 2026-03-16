<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EmailVerificationService
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function persistVerificationEmail(User $user): string
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $targetDir = $projectDir . '/var/emails';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $verifyUrl = sprintf('http://localhost:81/api/auth/verify/%s', $user->getVerificationToken());
        $body = implode(PHP_EOL, [
            sprintf('To: %s', $user->getEmail()),
            'Subject: Confirm your account',
            '',
            'Click the link below to confirm your account:',
            $verifyUrl,
            '',
            'This email is intentionally persisted to a file as requested in the challenge.',
        ]);

        $filename = sprintf('%s/%s_%s.txt', $targetDir, date('YmdHis'), preg_replace('/[^a-z0-9]+/i', '_', $user->getEmail()));
        file_put_contents($filename, $body);

        return $filename;
    }
}
