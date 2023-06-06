<?php

declare(strict_types=1);

/*
 * This file is part of terminal42/contao-password-validation.
 *
 * (c) terminal42 gmbh <https://terminal42.ch>
 *
 * @license MIT
 */

namespace Terminal42\PasswordValidationBundle\Validation\Validator;

use Contao\StringUtil;
use Contao\System;
use Terminal42\PasswordValidationBundle\Exception\PasswordValidatorException;
use Terminal42\PasswordValidationBundle\Validation\PasswordValidatorInterface;
use Terminal42\PasswordValidationBundle\Validation\ValidationConfiguration;
use Terminal42\PasswordValidationBundle\Validation\ValidationContext;

final class RequiredCharacters implements PasswordValidatorInterface
{
    private $configuration;

    public function __construct(ValidationConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function validate(ValidationContext $context): bool
    {
        if (false === $this->configuration->hasConfiguration($context->getUserEntity())) {
            return true;
        }

        $configuration = $this->configuration->getConfiguration($context->getUserEntity());
        $require = $configuration['require'] ?? null;

        if (!$require) {
            return true;
        }

        $password = $context->getPassword()->getString();

        $errors = [];

        foreach ($require as $category => $minimum) {
            if (!$minimum) {
                continue;
            }

            $actual = $this->countRequirement($category, $password, $context);

            if (null === $actual) {
                continue;
            }

            if ($actual < $minimum) {
                if ('other' === $category) {
                    $errors[] = new PasswordValidatorException(
                        sprintf($this->translate('required.other'), $minimum, $configuration['other_chars'] ?? null)
                    );
                    continue;
                }

                $errors[] = new PasswordValidatorException(sprintf($this->translate('required.'.$category), $minimum));
            }
        }

        if (\count($errors) > 1) {
            throw new PasswordValidatorException(sprintf($this->translate('required.summary'), $require['uppercase'] ?? null, $require['lowercase'] ?? null, $require['numbers'] ?? null, $require['other'] ?? null, $configuration['other_chars'] ?? null));
        }

        if (\count($errors) > 0) {
            throw array_pop($errors);
        }

        return true;
    }

    private function countRequirement(string $category, string $string, ValidationContext $context): ?int
    {
        switch ($category) {
            case 'lowercase':
                $uppercase = mb_strtoupper($string);

                return \strlen($uppercase) - similar_text($string, $uppercase);

            case 'uppercase':
                $lowercase = mb_strtolower($string);

                return \strlen($lowercase) - similar_text($string, $lowercase);

            case 'numbers':
                return \strlen(preg_replace('/\D+/', '', $string));

            case 'other':
                $chars = $this->getRequiredOtherCharactersForRegexp($context);

                if (null === $chars) {
                    return null;
                }

                return \strlen(preg_replace('/[^'.$chars.']+/', '', $string));

            default:
                return null;
        }
    }

    private function getRequiredOtherCharactersForRegexp(ValidationContext $context): ?string
    {
        $config = $this->configuration->getConfiguration($context->getUserEntity());
        $chars = $config['other_chars'] ?? null;

        if (!$chars) {
            return null;
        }

        $return = '';

        foreach (array_unique(preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY)) as $char) {
            $return .= '\\'.$char;
        }

        return $return;
    }

    private function translate(string $key)
    {
        System::loadLanguageFile('exception');

        [$key1, $key2] = StringUtil::trimsplit('.', $key);

        return $GLOBALS['TL_LANG']['XPT']['passwordValidation'][$key1][$key2];
    }
}
