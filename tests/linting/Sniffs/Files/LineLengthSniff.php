<?php

/**
 * Class Linting_Sniffs_Files_LineLengthSniff
 * This class extends the linter turned off by TerminusStandard.xml.
 * It is changed to ignore the length of lines within comment blocks.
 */
class Linting_Sniffs_Files_LineLengthSniff extends Generic_Sniffs_Files_LineLengthSniff
{
    /**
     * Notes the types of tokens which are comments so their lines can be skipped.
     *
     * @var array
     */
    CONST COMMENT_TOKENS = [
        T_DOC_COMMENT,
        T_DOC_COMMENT_STAR,
        T_DOC_COMMENT_WHITESPACE,
        T_DOC_COMMENT_TAG,
        T_DOC_COMMENT_OPEN_TAG,
        T_DOC_COMMENT_CLOSE_TAG,
        T_DOC_COMMENT_STRING,
    ];

    /**
     * The limit that the length of a line must not exceed.
     *
     * Set to zero (0) to disable.
     *
     * @var int
     */
    public $absoluteLineLimit = 0;

    /**
     * The limit that the length of a line should not exceed.
     *
     * @var int
     */
    public $lineLimit = 120;

    /**
     * @inheritDoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = 1; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['column'] === 1) {
                $lineIsComment = false;
                $j = $i;
                do {
                    $lineIsComment = $lineIsComment || in_array($tokens[$j]['code'], self::COMMENT_TOKENS);
                    $j++;
                } while (($tokens[$j]['column'] !== 1) && ($j < count($tokens) - $i) && !$lineIsComment);
                if (!$lineIsComment) {
                    $this->checkLineLength($phpcsFile, $tokens, $i);
                }
            }
        }

        $this->checkLineLength($phpcsFile, $tokens, $i);

        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);
    }
}
