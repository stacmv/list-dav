<?php

declare(strict_types=1);

namespace ListDAV\WebUI\Controllers;

use ListDAV\Module\ModuleRegistry;
use ListDAV\Trash\TrashManager;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\IFile;
use Sabre\DAV\INode;

class ApiController
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly TrashManager $trashManager,
        private readonly ICollection $rootCollection,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function browse(string $path): array
    {
        $path = '/' . trim($path, '/');

        try {
            $node = $this->getNodeByPath($path);

            if (!$node instanceof ICollection) {
                return [
                    'success' => false,
                    'error' => 'Not a directory',
                ];
            }

            $items = [];
            foreach ($node->getChildren() as $child) {
                $items[] = $this->nodeToArray($child, $path);
            }

            // Sort: directories first, then by name
            usort($items, function ($a, $b) {
                if ($a['isDir'] !== $b['isDir']) {
                    return $b['isDir'] <=> $a['isDir'];
                }
                return strcasecmp($a['name'], $b['name']);
            });

            return [
                'success' => true,
                'path' => $path,
                'items' => $items,
                'parent' => dirname($path) ?: '/',
            ];
        } catch (NotFound $e) {
            return [
                'success' => false,
                'error' => 'Path not found: ' . $path,
            ];
        }
    }

    /**
     * @param array<string> $sources
     * @return array<string, mixed>
     */
    public function copy(array $sources, string $destination): array
    {
        $results = [];
        $destination = '/' . trim($destination, '/');

        foreach ($sources as $source) {
            $source = '/' . trim($source, '/');
            $name = basename($source);
            $destPath = $destination . '/' . $name;

            try {
                $sourceNode = $this->getNodeByPath($source);
                $destCollection = $this->getNodeByPath($destination);

                if (!$destCollection instanceof ICollection) {
                    $results[] = ['source' => $source, 'success' => false, 'error' => 'Destination is not a directory'];
                    continue;
                }

                // Copy file content
                if ($sourceNode instanceof IFile) {
                    $content = $sourceNode->get();
                    $destCollection->createFile($name, $content);
                    $results[] = ['source' => $source, 'success' => true];
                } else {
                    // For directories, we'd need recursive copy
                    $results[] = ['source' => $source, 'success' => false, 'error' => 'Directory copy not yet implemented'];
                }
            } catch (\Exception $e) {
                $results[] = ['source' => $source, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * @param array<string> $sources
     * @return array<string, mixed>
     */
    public function move(array $sources, string $destination): array
    {
        $results = [];
        $destination = '/' . trim($destination, '/');

        foreach ($sources as $source) {
            $source = '/' . trim($source, '/');
            $name = basename($source);

            try {
                $sourceNode = $this->getNodeByPath($source);
                $destCollection = $this->getNodeByPath($destination);

                if (!$destCollection instanceof ICollection) {
                    $results[] = ['source' => $source, 'success' => false, 'error' => 'Destination is not a directory'];
                    continue;
                }

                // Copy then delete for move
                if ($sourceNode instanceof IFile) {
                    $content = $sourceNode->get();
                    $destCollection->createFile($name, $content);
                    $sourceNode->delete();
                    $results[] = ['source' => $source, 'success' => true];
                } else {
                    $results[] = ['source' => $source, 'success' => false, 'error' => 'Directory move not yet implemented'];
                }
            } catch (\Exception $e) {
                $results[] = ['source' => $source, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * @param array<string> $paths
     * @return array<string, mixed>
     */
    public function delete(array $paths): array
    {
        $results = [];

        foreach ($paths as $path) {
            $path = '/' . trim($path, '/');

            try {
                $node = $this->getNodeByPath($path);

                // Move to trash instead of permanent delete
                $module = $this->registry->getModuleForPath($path);
                $moduleName = $module ? $module->getName() : 'unknown';

                $this->trashManager->trash($path, $moduleName, [
                    'name' => $node->getName(),
                ]);

                $node->delete();
                $results[] = ['path' => $path, 'success' => true];
            } catch (\Exception $e) {
                $results[] = ['path' => $path, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * @return array<string, mixed>
     */
    public function rename(string $path, string $newName): array
    {
        $path = '/' . trim($path, '/');

        try {
            $node = $this->getNodeByPath($path);
            $node->setName($newName);

            return ['success' => true, 'newPath' => dirname($path) . '/' . $newName];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function createFolder(string $parentPath, string $name): array
    {
        $parentPath = '/' . trim($parentPath, '/');

        try {
            $parent = $this->getNodeByPath($parentPath);

            if (!$parent instanceof ICollection) {
                return ['success' => false, 'error' => 'Parent is not a directory'];
            }

            $parent->createDirectory($name);

            return ['success' => true, 'path' => $parentPath . '/' . $name];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getFile(string $path): array
    {
        $path = '/' . trim($path, '/');

        try {
            $node = $this->getNodeByPath($path);

            if (!$node instanceof IFile) {
                return ['success' => false, 'error' => 'Not a file'];
            }

            $content = $node->get();
            if (is_resource($content)) {
                $content = stream_get_contents($content);
            }

            return [
                'success' => true,
                'name' => $node->getName(),
                'content' => $content,
                'contentType' => $node->getContentType() ?? 'application/octet-stream',
                'size' => $node->getSize(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $query, string $path = '/'): array
    {
        $path = '/' . trim($path, '/');
        $results = [];
        $query = strtolower($query);

        try {
            $this->searchRecursive($this->getNodeByPath($path), $path, $query, $results);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * @param array<array<string, mixed>> $results
     */
    private function searchRecursive(INode $node, string $path, string $query, array &$results, int $depth = 0): void
    {
        if ($depth > 5) {
            return; // Limit recursion depth
        }

        if ($node instanceof ICollection) {
            foreach ($node->getChildren() as $child) {
                $childPath = $path . '/' . $child->getName();

                if (str_contains(strtolower($child->getName()), $query)) {
                    $results[] = $this->nodeToArray($child, $path);
                }

                if ($child instanceof ICollection) {
                    $this->searchRecursive($child, $childPath, $query, $results, $depth + 1);
                }
            }
        }
    }

    private function getNodeByPath(string $path): INode
    {
        $path = trim($path, '/');

        if ($path === '') {
            return $this->rootCollection;
        }

        $parts = explode('/', $path);
        $node = $this->rootCollection;

        foreach ($parts as $part) {
            if (!$node instanceof ICollection) {
                throw new NotFound("Path not found: {$path}");
            }
            $node = $node->getChild($part);
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function nodeToArray(INode $node, string $parentPath): array
    {
        $path = rtrim($parentPath, '/') . '/' . $node->getName();
        $isDir = $node instanceof ICollection;

        $item = [
            'name' => $node->getName(),
            'path' => $path,
            'isDir' => $isDir,
            'size' => 0,
            'modified' => date('Y-m-d H:i:s'),
        ];

        if ($node instanceof IFile) {
            $item['size'] = $node->getSize();
            $item['contentType'] = $node->getContentType() ?? 'application/octet-stream';

            $lastMod = $node->getLastModified();
            if ($lastMod) {
                $item['modified'] = date('Y-m-d H:i:s', $lastMod);
            }
        }

        return $item;
    }
}
