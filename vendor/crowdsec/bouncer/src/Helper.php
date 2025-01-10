<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

/**
 * Helper trait for Bouncer.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2021+ CrowdSec
 * @license   MIT License
 */
trait Helper
{
    /**
     * Build the raw body from superglobals.
     *
     * @param int      $maxBodySize the maximum body size in KB
     * @param resource $stream      The stream to read
     * @param array    $serverData  the $_SERVER superglobal
     * @param array    $postData    the $_POST superglobal
     * @param array    $filesData   the $_FILES superglobal
     *
     * @return string the raw body
     *
     * @throws BouncerException
     */
    private function buildRawBodyFromSuperglobals(
        int $maxBodySize,
        $stream,
        array $serverData = [], // $_SERVER
        array $postData = [], // $_POST
        array $filesData = [] // $_FILES
    ): string {
        $contentType = $serverData['CONTENT_TYPE'] ?? '';
        // The threshold is the maximum body size converted in bytes + 1
        $sizeThreshold = ($maxBodySize * 1024) + 1;

        if (false !== strpos($contentType, 'multipart/')) {
            return $this->getMultipartRawBody($contentType, $sizeThreshold, $postData, $filesData);
        }

        return $this->getRawInput($sizeThreshold, $stream);
    }

    private function appendFileData(
        array $fileArray,
        int $index,
        string $fileKey,
        string $boundary,
        int $threshold,
        int &$currentSize
    ): string {
        $fileName = is_array($fileArray['name']) ? $fileArray['name'][$index] : $fileArray['name'];
        $fileTmpName = is_array($fileArray['tmp_name']) ? $fileArray['tmp_name'][$index] : $fileArray['tmp_name'];
        $fileType = is_array($fileArray['type']) ? $fileArray['type'][$index] : $fileArray['type'];

        $headerPart = '--' . $boundary . "\r\n";
        $headerPart .= "Content-Disposition: form-data; name=\"$fileKey\"; filename=\"$fileName\"\r\n";
        $headerPart .= "Content-Type: $fileType\r\n\r\n";

        $currentSize += strlen($headerPart);
        if ($currentSize >= $threshold) {
            return substr($headerPart, 0, $threshold - ($currentSize - strlen($headerPart)));
        }

        $remainingSize = $threshold - $currentSize;
        $fileStream = fopen($fileTmpName, 'rb');
        $fileContent = $this->readStream($fileStream, $remainingSize);
        // Add 2 bytes for the \r\n at the end of the file content
        $currentSize += strlen($fileContent) + 2;

        return $headerPart . $fileContent . "\r\n";
    }

    private function buildFormData(string $boundary, string $key, string $value): string
    {
        return '--' . $boundary . "\r\n" .
               "Content-Disposition: form-data; name=\"$key\"\r\n\r\n" .
               "$value\r\n";
    }

    /**
     * Extract the boundary from the Content-Type.
     *
     *  Regex breakdown:
     *  /boundary="?([^;"]+)"?/i
     *
     *  - boundary=   : Matches the literal string 'boundary=' which indicates the start of the boundary parameter.
     *  - "?          : Matches an optional double quote that may surround the boundary value.
     *  - ([^;"]+)    : Captures one or more characters that are not a semicolon (;) or a double quote (") into a group.
     *                  This ensures the boundary is extracted accurately, stopping at a semicolon if present,
     *                  and avoiding the inclusion of quotes in the captured value.
     *  - "?          : Matches an optional closing double quote (if the boundary is quoted).
     *  - i           : Case-insensitive flag to handle 'boundary=' in any case (e.g., 'Boundary=' or 'BOUNDARY=').
     *
     * @throws BouncerException
     */
    private function extractBoundary(string $contentType): string
    {
        if (preg_match('/boundary="?([^;"]+)"?/i', $contentType, $matches)) {
            return trim($matches[1]);
        }
        throw new BouncerException("Failed to extract boundary from Content-Type: ($contentType)");
    }

    /**
     * Return the raw body for multipart requests.
     * This method will read the raw body up to the specified threshold.
     * If the body is too large, it will return a truncated version of the body up to the threshold.
     *
     * @throws BouncerException
     */
    private function getMultipartRawBody(
        string $contentType,
        int $threshold,
        array $postData,
        array $filesData
    ): string {
        try {
            $boundary = $this->extractBoundary($contentType);
            // Instead of concatenating strings, we will use an array to store the parts
            // and then join them with implode at the end to avoid performance issues.
            $parts = [];
            $currentSize = 0;

            foreach ($postData as $key => $value) {
                $formData = $this->buildFormData($boundary, $key, $value);
                $currentSize += strlen($formData);
                if ($currentSize >= $threshold) {
                    return substr(implode('', $parts) . $formData, 0, $threshold);
                }

                $parts[] = $formData;
            }

            foreach ($filesData as $fileKey => $fileArray) {
                $fileNames = is_array($fileArray['name']) ? $fileArray['name'] : [$fileArray['name']];
                foreach ($fileNames as $index => $fileName) {
                    $remainingSize = $threshold - $currentSize;
                    $fileData =
                        $this->appendFileData($fileArray, $index, $fileKey, $boundary, $remainingSize, $currentSize);
                    if ($currentSize >= $threshold) {
                        return substr(implode('', $parts) . $fileData, 0, $threshold);
                    }
                    $parts[] = $fileData;
                }
            }

            $endBoundary = '--' . $boundary . "--\r\n";
            $currentSize += strlen($endBoundary);

            if ($currentSize >= $threshold) {
                return substr(implode('', $parts) . $endBoundary, 0, $threshold);
            }

            $parts[] = $endBoundary;

            return implode('', $parts);
        } catch (\Throwable $e) {
            throw new BouncerException('Failed to read multipart raw body: ' . $e->getMessage());
        }
    }

    private function getRawInput(int $threshold, $stream): string
    {
        return $this->readStream($stream, $threshold);
    }

    /**
     * Read the stream up to the specified threshold.
     *
     * @param resource $stream    The stream to read
     * @param int      $threshold The maximum number of bytes to read
     *
     * @throws BouncerException
     */
    private function readStream($stream, int $threshold): string
    {
        if (!is_resource($stream)) {
            throw new BouncerException('Stream is not a valid resource');
        }
        $buffer = '';
        $chunkSize = 8192;
        $bytesRead = 0;
        // We make sure there won't be infinite loop
        $maxLoops = (int) ceil($threshold / $chunkSize);
        $loopCount = -1;

        try {
            while (!feof($stream) && $bytesRead < $threshold) {
                ++$loopCount;
                if ($loopCount >= $maxLoops) {
                    throw new BouncerException("Too many loops ($loopCount) while reading stream");
                }
                $remainingSize = $threshold - $bytesRead;
                $readLength = min($chunkSize, $remainingSize);

                $data = fread($stream, $readLength);
                if (false === $data) {
                    throw new BouncerException('Failed to read chunk from stream');
                }

                $buffer .= $data;
                $bytesRead += strlen($data);

                if ($bytesRead >= $threshold) {
                    break;
                }
            }

            return $buffer;
        } catch (\Throwable $e) {
            throw new BouncerException('Failed to read stream: ' . $e->getMessage());
        } finally {
            fclose($stream);
        }
    }
}
