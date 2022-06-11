<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\I18n;

/**
 * Copyright 2015-2020 info@neomerx.com.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
class Messages
{
    private static array $translations = [];

    /**
     * Try to translate the message and format it with the given parameters.
     *
     * @param mixed ...$parameters
     */
    public static function compose(string $message, ...$parameters): string
    {
        $translation = static::getTranslation($message);
        $result = false === empty($parameters) ? \vsprintf($translation, $parameters) : $translation;

        return $result;
    }

    /**
     * Translate message if configured or return the original untranslated message.
     *
     * @SuppressWarnings(PHPMD.UndefinedVariable) PHPMD currently has a glitch with `$message`
     */
    public static function getTranslation(string $message): string
    {
        return static::$translations[$message] ?? $message;
    }

    /**
     * Set translations for messages.
     */
    public static function setTranslations(array $translations): void
    {
        static::$translations = $translations;
    }
}
