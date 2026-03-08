# Arquitectura Multi-Juegos: Vue 3 + Laravel API

## 1. RESUMEN EJECUTIVO

**Stack Technology:**
- Frontend: Vue 3 + Composition API + Pinia (State Management)
- Backend: Laravel + API REST
- Base de Datos: MySQL/PostgreSQL
- Cache: Redis
- Queue: Laravel Queue (para jobs asincronos)
- Autenticación: JWT Token

**Objetivo:** Plataforma centralizada para múltiples juegos con:
- Progreso independiente por juego
- Leaderboards globales y por juego
- Sistema de logros compartido
- Validación de todas las acciones en servidor
- Sincronización en tiempo real

---

## 2. ESTRUCTURA DE DIRECTORIOS

```
proyecto-juegos/
│
├── backend/
│   ├── app/
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Game.php
│   │   │   ├── UserProgress.php
│   │   │   ├── GameSession.php
│   │   │   ├── Achievement.php
│   │   │   ├── UserAchievement.php
│   │   │   └── Leaderboard.php
│   │   │
│   │   ├── Http/Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── GameController.php
│   │   │   │   ├── LeaderboardController.php
│   │   │   │   └── AchievementController.php
│   │   │   └── Games/
│   │   │       ├── ClickerGameController.php
│   │   │       ├── PuzzleGameController.php
│   │   │       └── MemoryGameController.php
│   │   │
│   │   ├── Services/
│   │   │   ├── GameService.php (clase abstracta)
│   │   │   ├── Games/
│   │   │   │   ├── ClickerGameService.php
│   │   │   │   ├── PuzzleGameService.php
│   │   │   │   └── MemoryGameService.php
│   │   │   ├── AchievementService.php
│   │   │   ├── LeaderboardService.php
│   │   │   └── ValidationService.php
│   │   │
│   │   ├── Jobs/
│   │   │   ├── ProcessAchievements.php
│   │   │   ├── UpdateLeaderboard.php
│   │   │   └── ProcessGameSession.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── Games/
│   │   │   │   ├── ClickerActionRequest.php
│   │   │   │   └── PuzzleActionRequest.php
│   │   │   └── SaveGameStateRequest.php
│   │   │
│   │   ├── Resources/
│   │   │   ├── UserResource.php
│   │   │   ├── GameResource.php
│   │   │   ├── LeaderboardResource.php
│   │   │   └── AchievementResource.php
│   │   │
│   │   └── Middleware/
│   │       ├── ValidateGameAction.php
│   │       ├── RateLimitGame.php
│   │       └── GameAuthMiddleware.php
│   │
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 2024_01_01_000001_create_users_table.php
│   │   │   ├── 2024_01_01_000002_create_games_table.php
│   │   │   ├── 2024_01_01_000003_create_user_progress_table.php
│   │   │   ├── 2024_01_01_000004_create_leaderboards_table.php
│   │   │   ├── 2024_01_01_000005_create_achievements_table.php
│   │   │   ├── 2024_01_01_000006_create_user_achievements_table.php
│   │   │   └── 2024_01_01_000007_create_game_sessions_table.php
│   │   └── seeders/
│   │       └── GameSeeder.php
│   │
│   ├── routes/
│   │   ├── api.php
│   │   └── games.php
│   │
│   ├── config/
│   │   ├── games.php (configuración de cada juego)
│   │   └── rate_limit.php
│   │
│   └── tests/
│       └── Feature/
│           ├── GamesTest.php
│           ├── LeaderboardTest.php
│           └── AchievementTest.php
│
└── frontend/
    ├── src/
    │   ├── stores/
    │   │   ├── auth.js
    │   │   ├── user.js
    │   │   ├── global.js (achievements, leaderboard)
    │   │   └── games/
    │   │       ├── clicker.js
    │   │       ├── puzzle.js
    │   │       └── memory.js
    │   │
    │   ├── views/
    │   │   ├── Home.vue
    │   │   ├── GameLobby.vue
    │   │   ├── games/
    │   │   │   ├── ClickerGame.vue
    │   │   │   ├── PuzzleGame.vue
    │   │   │   └── MemoryGame.vue
    │   │   ├── Profile.vue
    │   │   ├── Leaderboard.vue
    │   │   └── Achievements.vue
    │   │
    │   ├── components/
    │   │   ├── GameHeader.vue
    │   │   ├── LeaderboardWidget.vue
    │   │   ├── AchievementNotification.vue
    │   │   └── GameLoadingOverlay.vue
    │   │
    │   ├── services/
    │   │   ├── gameEngineService.js
    │   │   ├── apiClient.js
    │   │   └── syncService.js
    │   │
    │   ├── utils/
    │   │   ├── debounce.js
    │   │   └── validators.js
    │   │
    │   └── router/
    │       └── index.js
    │
    └── tests/
        └── stores/
            └── games.spec.js
```

---

## 3. MODELOS DE BASE DE DATOS

### 3.1 Tabla: users

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    total_playtime INT UNSIGNED DEFAULT 0,
    total_score BIGINT UNSIGNED DEFAULT 0,
    total_games_played INT UNSIGNED DEFAULT 0,
    last_active_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_total_score (total_score)
);
```

### 3.2 Tabla: games

```sql
CREATE TABLE games (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    version VARCHAR(20) DEFAULT '1.0.0',
    is_active BOOLEAN DEFAULT TRUE,
    config JSON NOT NULL COMMENT 'Almacena configuración específica del juego',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
);
```

**Ejemplo de config JSON:**
```json
{
  "max_session_duration": 3600,
  "allowed_actions": ["click", "buy_upgrade", "prestige"],
  "rate_limit_per_minute": 100,
  "anti_cheat_enabled": true,
  "features": {
    "offline_mode": true,
    "leaderboard": true,
    "achievements": true
  }
}
```

### 3.3 Tabla: user_progress

```sql
CREATE TABLE user_progress (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    game_id INT UNSIGNED NOT NULL,
    game_state JSON NOT NULL COMMENT 'Estado del juego serializado',
    score BIGINT UNSIGNED DEFAULT 0,
    level INT UNSIGNED DEFAULT 1,
    playtime_seconds INT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    last_played_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_game (user_id, game_id),
    INDEX idx_score (score),
    INDEX idx_last_played (last_played_at)
);
```

### 3.4 Tabla: game_sessions

```sql
CREATE TABLE game_sessions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    game_id INT UNSIGNED NOT NULL,
    session_data JSON NOT NULL,
    score INT UNSIGNED DEFAULT 0,
    duration_seconds INT UNSIGNED DEFAULT 0,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    INDEX idx_user_game (user_id, game_id),
    INDEX idx_status (status)
);
```

### 3.5 Tabla: leaderboards

```sql
CREATE TABLE leaderboards (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    game_id INT UNSIGNED NOT NULL,
    score BIGINT UNSIGNED NOT NULL,
    rank INT UNSIGNED,
    percentile DECIMAL(5, 2),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_game (user_id, game_id),
    INDEX idx_game_score (game_id, score DESC),
    INDEX idx_rank (rank)
);
```

### 3.6 Tabla: achievements

```sql
CREATE TABLE achievements (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    game_id INT UNSIGNED NULLABLE,
    points_reward INT UNSIGNED DEFAULT 0,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    condition JSON NOT NULL COMMENT 'Condición para desbloquear',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL,
    INDEX idx_game (game_id),
    INDEX idx_active (is_active)
);
```

**Ejemplo de condition JSON:**
```json
{
  "type": "score_threshold",
  "value": 1000,
  "game_id": 1,
  "operator": "greater_than_or_equal"
}
```

### 3.7 Tabla: user_achievements

```sql
CREATE TABLE user_achievements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user (user_id),
    INDEX idx_unlocked (unlocked_at)
);
```

---

## 4. ENDPOINTS API

### 4.1 Autenticación (Global)

```
POST   /api/auth/register
       Body: { email, password, username }
       Response: { token, user }

POST   /api/auth/login
       Body: { email, password }
       Response: { token, user }

POST   /api/auth/refresh
       Headers: { Authorization: Bearer token }
       Response: { token }

POST   /api/auth/logout
       Headers: { Authorization: Bearer token }
       Response: { success: true }

GET    /api/auth/me
       Headers: { Authorization: Bearer token }
       Response: { id, email, username, ... }
```

### 4.2 Usuario (Global)

```
GET    /api/user/profile
       Response: { id, username, email, total_playtime, ... }

PATCH  /api/user/profile
       Body: { username, avatar_url, ... }
       Response: { updated user }

GET    /api/user/stats
       Response: { 
         total_playtime, 
         total_score, 
         games_played,
         game_breakdown: [
           { game_slug, score, playtime, level }
         ]
       }

GET    /api/user/achievements
       Response: { 
         total: number,
         unlocked: [achievement],
         locked: [achievement]
       }

GET    /api/user/inventory
       Response: { items: [{ slug, quantity, ... }] }
```

### 4.3 Juegos (Global)

```
GET    /api/games
       Response: { 
         games: [
           { id, slug, name, description, version }
         ]
       }

GET    /api/games/{gameSlug}
       Response: { id, slug, name, description, config, ... }

POST   /api/games/{gameSlug}/play
       Body: { load_save: boolean }
       Response: { 
         session_id, 
         game_state,
         user_progress
       }

POST   /api/games/{gameSlug}/action
       Body: { 
         action: string, 
         payload: object,
         timestamp: number
       }
       Response: { 
         success: boolean,
         game_state: object,
         events: [{ type, data }]
       }

POST   /api/games/{gameSlug}/save
       Body: { 
         game_state: object,
         score: number,
         playtime: number
       }
       Response: { 
         success: true,
         saved_at: timestamp
       }

GET    /api/games/{gameSlug}/load
       Response: { 
         game_state: object,
         score: number,
         playtime: number,
         last_played: timestamp
       }

POST   /api/games/{gameSlug}/complete
       Body: { 
         final_score: number,
         duration: number,
         session_data: object
       }
       Response: { 
         score, 
         rank_change, 
         achievements_unlocked: [achievement]
       }
```

### 4.4 Leaderboards (Global)

```
GET    /api/leaderboard/{gameSlug}?page=1&limit=100
       Response: { 
         game: object,
         entries: [
           { rank, user: { id, username }, score, percentile }
         ],
         user_rank: number,
         user_score: number
       }

GET    /api/leaderboard/{gameSlug}/friends
       Response: { 
         friends: [
           { rank, username, score, relative_rank }
         ]
       }

GET    /api/leaderboard/global
       Response: { 
         entries: [
           { rank, username, total_score, games_played }
         ]
       }
```

### 4.5 Logros (Global)

```
GET    /api/achievements
       Response: { 
         achievements: [
           { id, slug, name, description, icon_url, progress }
         ]
       }

GET    /api/achievements/{gameSlug}
       Response: { 
         achievements: [
           { id, slug, unlocked: boolean, ... }
         ]
       }

GET    /api/achievements/{achievementSlug}/progress
       Response: { 
         achievement: object,
         current_value: number,
         target_value: number,
         progress_percent: number
       }
```

---

## 5. SERVICIOS (Backend)

### 5.1 GameService.php (Clase Abstracta)

```php
namespace App\Services;

abstract class GameService {
    protected User $user;
    protected Game $game;
    protected int $gameId;
    
    /**
     * Guardar progreso del usuario
     */
    public function saveProgress(array $state, int $score, int $playtime = 0): UserProgress {
        $progress = UserProgress::updateOrCreate(
            ['user_id' => $this->user->id, 'game_id' => $this->gameId],
            [
                'game_state' => json_encode($state),
                'score' => $score,
                'playtime_seconds' => $playtime,
                'last_played_at' => now()
            ]
        );
        
        // Disparar jobs asincronos
        dispatch(new UpdateLeaderboard($this->user->id, $this->gameId, $score));
        dispatch(new ProcessAchievements($this->user->id, $this->gameId, $state, $score));
        
        return $progress;
    }
    
    /**
     * Cargar progreso del usuario
     */
    public function loadProgress(): ?UserProgress {
        return UserProgress::where('user_id', $this->user->id)
                           ->where('game_id', $this->gameId)
                           ->first();
    }
    
    /**
     * Crear sesión de juego
     */
    public function createSession(array $initialState): GameSession {
        return GameSession::create([
            'user_id' => $this->user->id,
            'game_id' => $this->gameId,
            'session_data' => json_encode($initialState),
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }
    
    /**
     * Completar sesión
     */
    public function completeSession(GameSession $session, int $finalScore, int $duration): array {
        $session->update([
            'score' => $finalScore,
            'duration_seconds' => $duration,
            'status' => 'completed',
            'ended_at' => now()
        ]);
        
        $this->saveProgress(
            json_decode($session->session_data, true),
            $finalScore,
            $duration
        );
        
        return [
            'success' => true,
            'score' => $finalScore,
            'rank_change' => $this->getRankChange()
        ];
    }
    
    /**
     * Ejecutar acción validada (implementada por subclases)
     */
    abstract public function executeAction(string $action, array $payload): array;
    
    /**
     * Validar acción (implementada por subclases)
     */
    abstract protected function validateAction(string $action, array $payload): bool;
}
```

### 5.2 ClickerGameService.php

```php
namespace App\Services\Games;

use App\Services\GameService;

class ClickerGameService extends GameService {
    
    public function __construct(Game $game) {
        $this->game = $game;
        $this->gameId = $game->id;
    }
    
    public function executeAction(string $action, array $payload): array {
        if (!$this->validateAction($action, $payload)) {
            return ['error' => 'Invalid action', 'code' => 'INVALID_ACTION'];
        }
        
        return match($action) {
            'click' => $this->handleClick($payload),
            'buy_upgrade' => $this->handleBuyUpgrade($payload),
            'prestige' => $this->handlePrestige($payload),
            'auto_clicker_tick' => $this->handleAutoClickerTick($payload),
            default => ['error' => 'Unknown action']
        };
    }
    
    private function handleClick(array $payload): array {
        // Validar timestamp (anti-cheat)
        $now = now()->getTimestamp() * 1000;
        if (abs($now - $payload['timestamp']) > 5000) { // Tolerancia 5s
            return ['error' => 'Invalid timestamp', 'code' => 'TIMESTAMP_MISMATCH'];
        }
        
        $progress = $this->loadProgress();
        $state = json_decode($progress->game_state, true);
        
        $clickPower = $state['click_power'] ?? 1;
        $state['balance'] += $clickPower;
        $state['total_clicks']++;
        
        // Guardar cada 20 clicks
        if ($state['total_clicks'] % 20 === 0) {
            $this->saveProgress($state, $state['balance']);
        }
        
        return [
            'success' => true,
            'balance' => $state['balance'],
            'total_clicks' => $state['total_clicks']
        ];
    }
    
    private function handleBuyUpgrade(array $payload): array {
        $upgradeId = $payload['upgrade_id'];
        $progress = $this->loadProgress();
        $state = json_decode($progress->game_state, true);
        
        $upgradeConfig = $this->getUpgradeConfig($upgradeId);
        $cost = $upgradeConfig['cost'];
        
        if ($state['balance'] < $cost) {
            return ['error' => 'Insufficient balance', 'code' => 'INSUFFICIENT_BALANCE'];
        }
        
        $state['balance'] -= $cost;
        $state['upgrades'][$upgradeId] = ($state['upgrades'][$upgradeId] ?? 0) + 1;
        $state['dps'] += $upgradeConfig['dps_bonus'] ?? 0;
        $state['click_power'] += $upgradeConfig['click_bonus'] ?? 0;
        
        $this->saveProgress($state, $state['balance']);
        
        return [
            'success' => true,
            'balance' => $state['balance'],
            'upgrades' => $state['upgrades'],
            'dps' => $state['dps']
        ];
    }
    
    private function handlePrestige(array $payload): array {
        $progress = $this->loadProgress();
        $state = json_decode($progress->game_state, true);
        
        $prestigeGain = floor(sqrt($state['balance']) * 10);
        
        $state = [
            'balance' => 0,
            'total_clicks' => 0,
            'click_power' => 1,
            'dps' => 0,
            'upgrades' => [],
            'prestige_level' => ($state['prestige_level'] ?? 0) + 1,
            'total_prestige_gains' => ($state['total_prestige_gains'] ?? 0) + $prestigeGain,
            'prestige_multiplier' => 1 + (($state['prestige_level'] ?? 0) * 0.1)
        ];
        
        $this->saveProgress($state, 0);
        
        return [
            'success' => true,
            'prestige_gained' => $prestigeGain,
            'prestige_level' => $state['prestige_level']
        ];
    }
    
    protected function validateAction(string $action, array $payload): bool {
        return match($action) {
            'click' => isset($payload['timestamp']),
            'buy_upgrade' => isset($payload['upgrade_id']),
            'prestige' => true,
            default => false
        };
    }
    
    private function getUpgradeConfig(int $upgradeId): array {
        return [
            1 => ['cost' => 10, 'dps_bonus' => 0.1, 'click_bonus' => 0],
            2 => ['cost' => 100, 'dps_bonus' => 1, 'click_bonus' => 0],
            // ... más upgrades
        ][$upgradeId] ?? [];
    }
}
```

### 5.3 AchievementService.php

```php
namespace App\Services;

class AchievementService {
    
    public function checkAndUnlock(User $user, int $gameId, array $triggerData): array {
        $newAchievements = [];
        
        $achievements = Achievement::where('is_active', true)
                                   ->where(function($q) use ($gameId) {
                                       $q->where('game_id', $gameId)
                                         ->orWhereNull('game_id');
                                   })
                                   ->get();
        
        foreach ($achievements as $achievement) {
            if ($this->isUnlocked($user, $achievement->id)) {
                continue; // Ya desbloqueado
            }
            
            if ($this->conditionMet($achievement->condition, $triggerData)) {
                $user->achievements()->attach($achievement->id, [
                    'unlocked_at' => now()
                ]);
                
                $newAchievements[] = $achievement;
                
                event(new AchievementUnlocked($user, $achievement));
            }
        }
        
        return $newAchievements;
    }
    
    private function conditionMet(array $condition, array $data): bool {
        $value = $data[$condition['field']] ?? null;
        
        if ($value === null) {
            return false;
        }
        
        return match($condition['operator']) {
            'greater_than' => $value > $condition['value'],
            'greater_than_or_equal' => $value >= $condition['value'],
            'equal' => $value === $condition['value'],
            'less_than' => $value < $condition['value'],
            'in_array' => in_array($value, $condition['value']),
            default => false
        };
    }
    
    private function isUnlocked(User $user, int $achievementId): bool {
        return $user->achievements()->where('achievement_id', $achievementId)->exists();
    }
}
```

### 5.4 LeaderboardService.php

```php
namespace App\Services;

class LeaderboardService {
    
    public function updateLeaderboard(int $userId, int $gameId, int $score): void {
        Cache::forget("leaderboard:{$gameId}");
        
        Leaderboard::updateOrCreate(
            ['user_id' => $userId, 'game_id' => $gameId],
            ['score' => $score, 'updated_at' => now()]
        );
        
        // Recalcular ranks (se hace en job asincronos)
        dispatch(new RecalculateRanks($gameId))->delay(now()->addSeconds(5));
    }
    
    public function getLeaderboard(int $gameId, int $page = 1, int $limit = 100): array {
        $cacheKey = "leaderboard:{$gameId}:page:{$page}";
        
        return Cache::remember($cacheKey, 300, function() use ($gameId, $page, $limit) {
            $entries = Leaderboard::where('game_id', $gameId)
                                  ->orderBy('score', 'desc')
                                  ->with('user:id,username,avatar_url')
                                  ->paginate($limit, ['*'], 'page', $page);
            
            return [
                'entries' => $entries->items(),
                'total' => $entries->total(),
                'current_page' => $page,
                'pages' => $entries->lastPage()
            ];
        });
    }
    
    public function getUserRank(int $userId, int $gameId): array {
        $rank = Leaderboard::where('game_id', $gameId)
                          ->where('score', '>', 
                              Leaderboard::where('user_id', $userId)
                                        ->where('game_id', $gameId)
                                        ->value('score') ?? 0
                          )
                          ->count() + 1;
        
        $leaderboard = Leaderboard::where('game_id', $gameId)
                                  ->where('user_id', $userId)
                                  ->first();
        
        return [
            'rank' => $rank,
            'score' => $leaderboard->score ?? 0,
            'percentile' => $this->calculatePercentile($rank, $gameId)
        ];
    }
    
    private function calculatePercentile(int $rank, int $gameId): float {
        $total = Leaderboard::where('game_id', $gameId)->count();
        return round((1 - ($rank / $total)) * 100, 2);
    }
}
```

---

## 6. CONTROLADORES (Backend)

### 6.1 GameController.php

```php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GameController extends Controller {
    
    public function __construct(
        private GameService $gameService,
        private AchievementService $achievementService,
        private ValidationService $validationService
    ) {}
    
    /**
     * Obtener información del juego
     */
    public function show(string $gameSlug): JsonResponse {
        $game = Game::where('slug', $gameSlug)
                   ->where('is_active', true)
                   ->firstOrFail();
        
        return response()->json(new GameResource($game));
    }
    
    /**
     * Iniciar sesión de juego
     */
    public function play(Request $request, string $gameSlug): JsonResponse {
        $user = auth()->user();
        $game = Game::where('slug', $gameSlug)
                   ->where('is_active', true)
                   ->firstOrFail();
        
        $loadSave = $request->boolean('load_save', false);
        $gameService = $this->getGameService($game->slug, $user, $game);
        
        if ($loadSave) {
            $progress = $gameService->loadProgress();
            if ($progress) {
                $gameState = json_decode($progress->game_state, true);
            } else {
                $gameState = $this->getInitialGameState($game->slug);
            }
        } else {
            $gameState = $this->getInitialGameState($game->slug);
        }
        
        $session = $gameService->createSession($gameState);
        
        return response()->json([
            'session_id' => $session->id,
            'game_state' => $gameState,
            'game' => new GameResource($game)
        ]);
    }
    
    /**
     * Ejecutar acción en juego
     */
    public function action(Request $request, string $gameSlug): JsonResponse {
        $user = auth()->user();
        $game = Game::where('slug', $gameSlug)
                   ->where('is_active', true)
                   ->firstOrFail();
        
        $request->validate([
            'action' => 'required|string|max:50',
            'payload' => 'required|array',
            'timestamp' => 'required|integer'
        ]);
        
        // Validar rate limiting
        if (!$this->validationService->checkRateLimit($user->id, $game->id)) {
            return response()->json(
                ['error' => 'Rate limit exceeded'],
                429
            );
        }
        
        $gameService = $this->getGameService($game->slug, $user, $game);
        $result = $gameService->executeAction(
            $request->input('action'),
            $request->input('payload')
        );
        
        if (isset($result['error'])) {
            return response()->json($result, 422);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'timestamp' => now()->getTimestamp()
        ]);
    }
    
    /**
     * Guardar progreso
     */
    public function save(Request $request, string $gameSlug): JsonResponse {
        $user = auth()->user();
        $game = Game::where('slug', $gameSlug)->firstOrFail();
        
        $validated = $request->validate([
            'game_state' => 'required|array',
            'score' => 'required|integer|min:0',
            'playtime' => 'required|integer|min:0'
        ]);
        
        $gameService = $this->getGameService($game->slug, $user, $game);
        $gameService->saveProgress(
            $validated['game_state'],
            $validated['score'],
            $validated['playtime']
        );
        
        return response()->json([
            'success' => true,
            'saved_at' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Cargar progreso
     */
    public function load(string $gameSlug): JsonResponse {
        $user = auth()->user();
        $game = Game::where('slug', $gameSlug)->firstOrFail();
        
        $gameService = $this->getGameService($game->slug, $user, $game);
        $progress = $gameService->loadProgress();
        
        if (!$progress) {
            return response()->json([
                'game_state' => $this->getInitialGameState($game->slug),
                'score' => 0,
                'playtime' => 0,
                'last_played' => null
            ]);
        }
        
        return response()->json([
            'game_state' => json_decode($progress->game_state, true),
            'score' => $progress->score,
            'playtime' => $progress->playtime_seconds,
            'last_played' => $progress->last_played_at
        ]);
    }
    
    /**
     * Completar sesión de juego
     */
    public function complete(Request $request, string $gameSlug): JsonResponse {
        $user = auth()->user();
        $game = Game::where('slug', $gameSlug)->firstOrFail();
        
        $validated = $request->validate([
            'session_id' => 'required|exists:game_sessions,id',
            'final_score' => 'required|integer|min:0',
            'duration' => 'required|integer|min:0'
        ]);
        
        $session = GameSession::findOrFail($validated['session_id']);
        
        if ($session->user_id !== $user->id || $session->game_id !== $game->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $gameService = $this->getGameService($game->slug, $user, $game);
        $result = $gameService->completeSession(
            $session,
            $validated['final_score'],
            $validated['duration']
        );
        
        return response()->json($result);
    }
    
    private function getGameService(string $gameSlug, User $user, Game $game): GameService {
        return match($gameSlug) {
            'clicker' => new ClickerGameService($game),
            'puzzle' => new PuzzleGameService($game),
            'memory' => new MemoryGameService($game),
            default => throw new \Exception("Unknown game")
        };
    }
    
    private function getInitialGameState(string $gameSlug): array {
        return match($gameSlug) {
            'clicker' => [
                'balance' => 0,
                'click_power' => 1,
                'dps' => 0,
                'upgrades' => [],
                'total_clicks' => 0,
                'prestige_level' => 0
            ],
            'puzzle' => [
                'level' => 1,
                'moves' => 0,
                'board' => $this->generatePuzzleBoard()
            ],
            'memory' => [
                'level' => 1,
                'matched_pairs' => 0,
                'cards' => $this->generateMemoryCards()
            ],
            default => []
        };
    }
}
```

---

## 7. FRONTEND - PINIA STORES

### 7.1 stores/games/clicker.js

```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import gameEngineService from '@/services/gameEngineService'

export const useClickerStore = defineStore('clicker', () => {
  const gameSlug = 'clicker'
  const isLoading = ref(false)
  const error = ref(null)
  const sessionId = ref(null)
  const lastSaved = ref(null)
  
  // Game State
  const gameState = ref({
    balance: 0,
    click_power: 1,
    dps: 0,
    upgrades: [],
    total_clicks: 0,
    prestige_level: 0,
    total_playtime: 0
  })
  
  // Computed
  const balance = computed(() => gameState.value.balance)
  const clickPower = computed(() => gameState.value.click_power)
  const dps = computed(() => gameState.value.dps)
  const upgrades = computed(() => gameState.value.upgrades)
  
  // Actions
  async function initializeGame(loadSave = true) {
    isLoading.value = true
    try {
      const response = await gameEngineService.play(gameSlug, loadSave)
      sessionId.value = response.session_id
      Object.assign(gameState.value, response.game_state)
    } catch (e) {
      error.value = e.message
    } finally {
      isLoading.value = false
    }
  }
  
  async function click() {
    try {
      const result = await gameEngineService.action(gameSlug, {
        action: 'click',
        payload: {
          timestamp: Date.now(),
          click_count: gameState.value.total_clicks
        }
      })
      
      if (result.success) {
        gameState.value.balance = result.data.balance
        gameState.value.total_clicks = result.data.total_clicks
      }
    } catch (e) {
      error.value = e.message
    }
  }
  
  async function buyUpgrade(upgradeId) {
    try {
      const result = await gameEngineService.action(gameSlug, {
        action: 'buy_upgrade',
        payload: { upgrade_id: upgradeId }
      })
      
      if (result.success) {
        gameState.value.balance = result.data.balance
        gameState.value.upgrades = result.data.upgrades
        gameState.value.dps = result.data.dps
      }
    } catch (e) {
      error.value = e.message
    }
  }
  
  async function prestige() {
    try {
      const result = await gameEngineService.action(gameSlug, {
        action: 'prestige',
        payload: {}
      })
      
      if (result.success) {
        gameState.value.balance = 0
        gameState.value.upgrades = []
        gameState.value.total_clicks = 0
        gameState.value.prestige_level = result.data.prestige_level
      }
    } catch (e) {
      error.value = e.message
    }
  }
  
  async function saveGame() {
    try {
      await gameEngineService.save(gameSlug, {
        game_state: gameState.value,
        score: gameState.value.balance,
        playtime: gameState.value.total_playtime
      })
      lastSaved.value = new Date()
    } catch (e) {
      error.value = e.message
    }
  }
  
  async function loadGame() {
    try {
      const data = await gameEngineService.load(gameSlug)
      Object.assign(gameState.value, data.game_state)
    } catch (e) {
      error.value = e.message
    }
  }
  
  async function completeGame() {
    try {
      const result = await gameEngineService.complete(gameSlug, {
        session_id: sessionId.value,
        final_score: gameState.value.balance,
        duration: gameState.value.total_playtime
      })
      
      return result
    } catch (e) {
      error.value = e.message
      throw e
    }
  }
  
  return {
    // State
    gameState,
    isLoading,
    error,
    lastSaved,
    // Computed
    balance,
    clickPower,
    dps,
    upgrades,
    // Actions
    initializeGame,
    click,
    buyUpgrade,
    prestige,
    saveGame,
    loadGame,
    completeGame
  }
})
```

### 7.2 services/gameEngineService.js

```javascript
import apiClient from './apiClient'
import { debounce } from '@/utils/debounce'

class GameEngineService {
  constructor() {
    this.saveQueue = {}
    this.syncInterval = null
  }
  
  /**
   * Obtener info del juego
   */
  async getGameInfo(gameSlug) {
    return apiClient.get(`/games/${gameSlug}`)
  }
  
  /**
   * Iniciar juego
   */
  async play(gameSlug, loadSave = true) {
    return apiClient.post(`/games/${gameSlug}/play`, {
      load_save: loadSave
    })
  }
  
  /**
   * Ejecutar acción
   */
  async action(gameSlug, data) {
    return apiClient.post(`/games/${gameSlug}/action`, {
      action: data.action,
      payload: data.payload,
      timestamp: data.timestamp || Date.now()
    })
  }
  
  /**
   * Guardar progreso (con debounce)
   */
  save(gameSlug, data) {
    if (!this.saveQueue[gameSlug]) {
      this.saveQueue[gameSlug] = debounce(
        () => this._performSave(gameSlug, data),
        5000
      )
    }
    
    this.saveQueue[gameSlug](data)
  }
  
  async _performSave(gameSlug, data) {
    return apiClient.post(`/games/${gameSlug}/save`, {
      game_state: data.game_state,
      score: data.score,
      playtime: data.playtime
    })
  }
  
  /**
   * Cargar progreso
   */
  async load(gameSlug) {
    return apiClient.get(`/games/${gameSlug}/load`)
  }
  
  /**
   * Completar sesión
   */
  async complete(gameSlug, data) {
    return apiClient.post(`/games/${gameSlug}/complete`, {
      session_id: data.session_id,
      final_score: data.final_score,
      duration: data.duration
    })
  }
}

export default new GameEngineService()
```

---

## 8. VALIDACIÓN Y SEGURIDAD

### 8.1 Middleware: ValidateGameAction

```php
namespace App\Http\Middleware;

class ValidateGameAction {
    public function handle($request, Closure $next) {
        $gameSlug = $request->route('gameSlug');
        $game = Game::where('slug', $gameSlug)->firstOrFail();
        $action = $request->input('action');
        
        // Whitelist de acciones
        $allowedActions = json_decode($game->config)->allowed_actions ?? [];
        if (!in_array($action, $allowedActions)) {
            return response()->json(['error' => 'Invalid action'], 422);
        }
        
        return $next($request);
    }
}
```

### 8.2 Validación General: ValidationService

```php
namespace App\Services;

class ValidationService {
    
    /**
     * Rate limiting por usuario-juego
     */
    public function checkRateLimit(int $userId, int $gameId, int $limit = 100): bool {
        $key = "rate_limit:{$userId}:{$gameId}";
        $count = Cache::increment($key);
        
        if ($count === 1) {
            Cache::expire($key, 60);
        }
        
        return $count <= $limit;
    }
    
    /**
     * Detectar comportamiento sospechoso
     */
    public function detectCheating(int $userId, int $gameId, array $newState, int $previousScore): bool {
        $maxScoreIncrease = 1000000; // Máximo aumento realista
        $scoreIncrease = $newState['score'] ?? 0 - $previousScore;
        
        if ($scoreIncrease > $maxScoreIncrease) {
            Log::warning("Suspicious score increase", [
                'user_id' => $userId,
                'game_id' => $gameId,
                'increase' => $scoreIncrease
            ]);
            return true;
        }
        
        return false;
    }
}
```

---

## 9. JOBS ASINCRONOS

### 9.1 Jobs/UpdateLeaderboard.php

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLeaderboard implements ShouldQueue {
    use Queueable;
    
    public function __construct(
        private int $userId,
        private int $gameId,
        private int $score
    ) {}
    
    public function handle(LeaderboardService $service) {
        $service->updateLeaderboard($this->userId, $this->gameId, $this->score);
    }
}
```

### 9.2 Jobs/ProcessAchievements.php

```php
namespace App\Jobs;

class ProcessAchievements implements ShouldQueue {
    use Queueable;
    
    public function __construct(
        private int $userId,
        private int $gameId,
        private array $gameState,
        private int $score
    ) {}
    
    public function handle(AchievementService $service, User $user) {
        $service->checkAndUnlock(
            $user,
            $this->gameId,
            array_merge($this->gameState, ['score' => $this->score])
        );
    }
}
```

---

## 10. CHECKLIST DE IMPLEMENTACIÓN

### Backend (Laravel)
- [ ] Crear migraciones de todas las tablas
- [ ] Crear modelos de Eloquent con relaciones
- [ ] Implementar GameService base y subclases
- [ ] Crear controladores API
- [ ] Implementar middleware de validación y rate limiting
- [ ] Configurar rutas API dinámicas
- [ ] Crear servicios de Achievements y Leaderboard
- [ ] Implementar Jobs para procesamiento asincronos
- [ ] Configurar caché Redis
- [ ] Tests unitarios de servicios
- [ ] Tests de integración de API

### Frontend (Vue 3)
- [ ] Crear stores Pinia para cada juego
- [ ] Implementar GameEngineService
- [ ] Crear vistas de juegos
- [ ] Implementar sincronización automática
- [ ] Crear componentes compartidos
- [ ] Manejo de errores y offline
- [ ] Tests de stores
- [ ] Optimización de rendering

### Común
- [ ] Documentación API (OpenAPI/Swagger)
- [ ] Setup CI/CD
- [ ] Logging y monitoreo
- [ ] Rate limiting y anti-fraud
- [ ] Backup de BD
- [ ] Ambiente de producción

---

## 11. VARIABLES DE CONFIGURACIÓN

### .env (Backend)

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=games_platform
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

JWT_SECRET=your-secret-key
JWT_ALGORITHM=HS256
JWT_TTL=7200

RATE_LIMIT_GAMES=100 # Acciones por minuto por juego
RATE_LIMIT_AUTH=5 # Intentos login por minuto
```

---

## 12. REFERENCIAS Y EXTENSIBILIDAD

Este documento define una arquitectura extensible donde:

1. **Agregar nuevo juego**: Crear `NuevoGameService extends GameService`
2. **Agregar logro**: Insertar en tabla `achievements` con condición JSON
3. **Agregar upgrade/item**: Modificar config del juego o tabla dedicada
4. **WebSockets**: Implementar con Laravel Broadcasting + Echo

---

**Fecha de creación:** 2024
**Versión:** 1.0.0
**Status:** Arquitectura de producción listo para desarrollo
