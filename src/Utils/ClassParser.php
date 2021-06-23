<?php

namespace Dev\ApiDocBundle\Utils;

use PhpToken;

class ClassParser
{
    /**
     * Returns namespaced class in file and null if there is no class
     *
     * @param string $file
     * @return string|null
     */
    public function extractClass(string $file): ?string
    {
        $namespace = '';
        $tokens = PhpToken::tokenize(file_get_contents($file));

        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i]->getTokenName() === 'T_NAMESPACE') {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j]->getTokenName() === 'T_NAME_QUALIFIED' ||  $tokens[$j]->getTokenName() === 'T_STRING') {
                        $namespace = $tokens[$j]->text;
                        break;
                    }
                }
            }

            if ($tokens[$i]->getTokenName() === 'T_CLASS' && $tokens[$i-1]->getTokenName() !== 'T_DOUBLE_COLON') {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j]->getTokenName() === 'T_WHITESPACE') {
                        continue;
                    }

                    if ($tokens[$j]->getTokenName() === 'T_STRING') {
                        return $namespace.'\\'.$tokens[$j]->text;
                    }
                }
            }
        }

        return null;
    }
}