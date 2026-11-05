<?php

namespace Pantheon\Terminus\Traits;

/**
 * Shared validation for site build paths (monorepo subdirectories).
 */
trait BuildPathTrait
{
    /**
     * Validate a repo-relative build path used for monorepo deploys.
     *
     * Mirrors the server-side validation in go-vcs-service (CheckBuildPath):
     * empty is allowed (means repo root); otherwise the path must be relative,
     * use forward slashes, contain only safe characters, and include no
     * parent-directory traversal.
     *
     * @param string $build_path The path to validate.
     * @return string|null Error message if invalid, or null if valid.
     */
    public static function validateBuildPath(string $build_path): ?string
    {
        if ($build_path === '') {
            return null;
        }
        if (strlen($build_path) > 512) {
            return 'build path must not exceed 512 characters';
        }
        if (strpos($build_path, '\\') !== false) {
            return 'build path must use forward slashes';
        }
        if (strpos($build_path, '/') === 0) {
            return 'build path must be a relative path (no leading slash)';
        }
        foreach (explode('/', $build_path) as $segment) {
            if ($segment === '') {
                return 'build path must not contain empty segments';
            }
            if ($segment === '.' || $segment === '..') {
                return "build path must not contain '.' or '..' segments";
            }
            if (!preg_match('/^[A-Za-z0-9._-]+$/', $segment)) {
                return sprintf('build path segment "%s" contains invalid characters', $segment);
            }
        }
        return null;
    }
}
