<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube\Services;

use ListDAV\Core\Database;

class YouTubeClient
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly Database $database,
    ) {
        $this->loadToken();
    }

    private function loadToken(): void
    {
        $result = $this->database->query(
            'SELECT * FROM oauth_tokens WHERE provider = :provider',
            ['provider' => 'youtube']
        );

        if (!empty($result)) {
            $this->accessToken = $result[0]['access_token'];
        }
    }

    public function isAuthenticated(): bool
    {
        return $this->accessToken !== null;
    }

    public function setAccessToken(string $token, ?string $refreshToken = null, ?string $expiresAt = null): void
    {
        $this->accessToken = $token;

        $this->database->execute(
            'INSERT OR REPLACE INTO oauth_tokens (provider, access_token, refresh_token, expires_at)
             VALUES (:provider, :access, :refresh, :expires)',
            [
                'provider' => 'youtube',
                'access' => $token,
                'refresh' => $refreshToken,
                'expires' => $expiresAt,
            ]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPlaylists(): array
    {
        // For demo/testing, return mock data if not authenticated
        if (!$this->isAuthenticated()) {
            return $this->getMockPlaylists();
        }

        // Real API call would go here
        return $this->getMockPlaylists();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPlaylistItems(string $playlistId): array
    {
        if (!$this->isAuthenticated()) {
            return $this->getMockVideos($playlistId);
        }

        // Real API call would go here
        return $this->getMockVideos($playlistId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVideoDetails(string $videoId): ?array
    {
        // Real API call would go here
        return [
            'id' => $videoId,
            'title' => "Video {$videoId}",
            'duration' => 'PT3M45S',
            'publishedAt' => '2024-01-01T00:00:00Z',
        ];
    }

    public function addToPlaylist(string $playlistId, string $videoId): void
    {
        // Real API call would go here
    }

    public function removeFromPlaylist(string $playlistItemId): void
    {
        // Real API call would go here
    }

    public function createPlaylist(string $title, string $description = ''): string
    {
        // Real API call would go here
        return 'new-playlist-' . uniqid();
    }

    public function deletePlaylist(string $playlistId): void
    {
        // Real API call would go here
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMockPlaylists(): array
    {
        return [
            [
                'id' => 'PLdemo1',
                'title' => 'Music',
                'description' => 'My music playlist',
                'itemCount' => 3,
            ],
            [
                'id' => 'PLdemo2',
                'title' => 'Tutorials',
                'description' => 'Programming tutorials',
                'itemCount' => 2,
            ],
            [
                'id' => 'PLdemo3',
                'title' => 'Watch Later',
                'description' => 'Videos to watch',
                'itemCount' => 4,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMockVideos(string $playlistId): array
    {
        $videos = [
            'PLdemo1' => [
                ['id' => 'dQw4w9WgXcQ', 'title' => 'Never Gonna Give You Up', 'duration' => 'PT3M33S', 'playlistItemId' => 'item1'],
                ['id' => 'fJ9rUzIMcZQ', 'title' => 'Bohemian Rhapsody', 'duration' => 'PT5M55S', 'playlistItemId' => 'item2'],
                ['id' => '9bZkp7q19f0', 'title' => 'Gangnam Style', 'duration' => 'PT4M13S', 'playlistItemId' => 'item3'],
            ],
            'PLdemo2' => [
                ['id' => 'rfscVS0vtbw', 'title' => 'Learn Python', 'duration' => 'PT4H20M', 'playlistItemId' => 'item4'],
                ['id' => 'PkZNo7MFNFg', 'title' => 'Learn JavaScript', 'duration' => 'PT3H26M', 'playlistItemId' => 'item5'],
            ],
            'PLdemo3' => [
                ['id' => 'video1', 'title' => 'Interesting Video 1', 'duration' => 'PT10M', 'playlistItemId' => 'item6'],
                ['id' => 'video2', 'title' => 'Interesting Video 2', 'duration' => 'PT15M', 'playlistItemId' => 'item7'],
                ['id' => 'video3', 'title' => 'Interesting Video 3', 'duration' => 'PT8M', 'playlistItemId' => 'item8'],
                ['id' => 'video4', 'title' => 'Interesting Video 4', 'duration' => 'PT12M', 'playlistItemId' => 'item9'],
            ],
        ];

        return $videos[$playlistId] ?? [];
    }
}
