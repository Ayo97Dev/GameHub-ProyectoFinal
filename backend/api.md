# GameHub API Documentation

This document describes the available endpoints for the GameHub API. The API uses Laravel Sanctum for token-based (stateless) authentication.

## Base URL
`http://localhost:8000/api`

## Authentication

Authentication is handled via Bearer tokens. All endpoints marked as **Auth Required** must include the following header:

`Authorization: Bearer <your_access_token>`

---

### 1. Register a New User
Create a new user account and receive an access token.

- **Endpoint:** `POST /register`
- **Auth Required:** No
- **Body (JSON):**
  ```json
  {
    "name": "Player One",
    "email": "player@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "message": "User registered successfully",
    "user": {
      "id": 1,
      "name": "Player One",
      "email": "player@example.com",
      "avatar": null,
      "bio": null,
      "global_stats": []
    },
    "access_token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz123456",
    "token_type": "Bearer"
  }
  ```

### 2. Login
Authenticate an existing user and receive an access token.

- **Endpoint:** `POST /login`
- **Auth Required:** No
- **Body (JSON):**
  ```json
  {
    "email": "player@example.com",
    "password": "password123"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "message": "Logged in successfully",
    "user": {
      "id": 1,
      "name": "Player One",
      "email": "player@example.com",
      "avatar": null,
      "bio": null,
      "global_stats": [
        {
          "game_id": 1,
          "high_score": 1500,
          "time_played": 3600,
          "last_played_at": "2026-02-23T10:00:00.000000Z",
          "game": {
            "id": 1,
            "slug": "space-invaders",
            "title": "Space Invaders",
            "description": "Classic arcade shooter."
          }
        }
      ]
    },
    "access_token": "2|ZaYxWvUtSrQpOnMlKjIhGfEdCb098765",
    "token_type": "Bearer"
  }
  ```

### 3. Get Current User
Retrieve the profile and global statistics of the currently authenticated user.

- **Endpoint:** `GET /user`
- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  {
    "data": {
      "id": 1,
      "name": "Player One",
      "email": "player@example.com",
      "avatar": null,
      "bio": null,
      "global_stats": [ ... ]
    }
  }
  ```

### 4. Logout
Invalidate the current session.

- **Endpoint:** `POST /logout`
- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  {
    "message": "Logged out successfully"
  }
  ```

---

## Games

### 5. List All Games
Get a list of all available games.

- **Endpoint:** `GET /games`
- **Auth Required:** No
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "slug": "space-invaders",
        "title": "Space Invaders",
        "description": "Classic arcade shooter."
      },
      {
        "id": 2,
        "slug": "cookie-clicker",
        "title": "Cookie Clicker",
        "description": "Click the cookie to earn points."
      }
    ]
  }
  ```

### 6. Get Game Details
Get details for a specific game using its slug.

- **Endpoint:** `GET /games/{slug}`
- **Auth Required:** No
- **Response (200 OK):**
  ```json
  {
    "data": {
      "id": 1,
      "slug": "space-invaders",
      "title": "Space Invaders",
      "description": "Classic arcade shooter."
    }
  }
  ```

---

## Game Saves (Persistence)

### 7. Save Game State
Save or update the game state for the authenticated user. This performs an "upsert" (updates if exists, creates if not).

- **Endpoint:** `POST /games/{gameId}/save`
- **Auth Required:** Yes
- **Parameters:** `gameId` (integer) - The ID of the game.
- **Body (JSON):**
  ```json
  {
    "payload": {
      "level": 5,
      "inventory": ["sword", "shield"],
      "position": {"x": 100, "y": 200}
    }
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "data": {
      "game_id": 1,
      "payload": {
        "level": 5,
        "inventory": ["sword", "shield"],
        "position": {"x": 100, "y": 200}
      },
      "updated_at": "2026-02-23T10:15:00.000000Z"
    }
  }
  ```

### 8. Load Game State
Retrieve the last saved game state for the authenticated user.

- **Endpoint:** `GET /games/{gameId}/save`
- **Auth Required:** Yes
- **Parameters:** `gameId` (integer) - The ID of the game.
- **Response (200 OK):**
  ```json
  {
    "data": {
      "game_id": 1,
      "payload": {
        "level": 5,
        "inventory": ["sword", "shield"],
        "position": {"x": 100, "y": 200}
      },
      "updated_at": "2026-02-23T10:15:00.000000Z"
    }
  }
  ```
  *(Returns `404 Not Found` if no save exists for this user and game).*

---

## Statistics & Leaderboards

### 9. Update Game Stats
Update the user's statistics for a specific game. The `time_played` is added to the existing total, and `high_score` is only updated if the provided value is greater than the current high score.

- **Endpoint:** `POST /games/{gameId}/stats`
- **Auth Required:** Yes
- **Parameters:** `gameId` (integer) - The ID of the game.
- **Body (JSON):**
  ```json
  {
    "high_score": 2500,
    "time_played": 120  // Time played in this session (e.g., seconds)
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "data": {
      "game_id": 1,
      "high_score": 2500,
      "time_played": 3720,
      "last_played_at": "2026-02-23T10:20:00.000000Z",
      "game": {
        "id": 1,
        "slug": "space-invaders",
        "title": "Space Invaders",
        "description": "Classic arcade shooter."
      }
    }
  }
  ```

### 10. Get Game Leaderboard
Retrieve the top 10 players with the highest scores for a specific game.

- **Endpoint:** `GET /leaderboard/{slug}`
- **Auth Required:** No
- **Parameters:** `slug` (string) - The slug of the game.
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "user_id": 5,
        "username": "ProGamer",
        "avatar": "https://example.com/avatar.png",
        "high_score": 9999,
        "time_played": 50000
      }
    ]
  }
  ```

---

## Game Engine (Motor de Juego)

### 11. Start Game Session
Starts a new game session. Optionally loads the previous save.

- **Endpoint:** `POST /games/{slug}/play`
- **Auth Required:** Yes
- **Body (JSON):**
  ```json
  { "load_save": true }
  ```
- **Response (200 OK):**
  ```json
  {
    "session_id": 42,
    "game_state": { "balance": 0, "click_power": 1, "dps": 0, "upgrades": {}, "total_clicks": 0, "prestige_level": 0 },
    "game": { "id": 1, "slug": "clicker", "title": "Neon Clicker Rush", "is_active": true }
  }
  ```

### 12. Execute Game Action
Executes a validated action within the game. Rate limited at the per-game configured limit.

- **Endpoint:** `POST /games/{slug}/action`
- **Auth Required:** Yes
- **Middleware:** `game.throttle` (rate limit per minute, configurable per game)
- **Body (JSON):**
  ```json
  {
    "action": "click",
    "payload": { "count": 5, "timestamp": 1741420800000 },
    "timestamp": 1741420800000
  }
  ```
- **Available actions (clicker):**
  - `click` — `payload.count` (int, 1–100): clics en lote procesados de una sola vez.
  - `buy_upgrade` — `payload.upgrade_id` (int): compra una mejora. El coste escala con `baseCost × 1.15^n` donde `n` es el número de veces ya comprada.
  - `prestige` — sin payload.
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": { "balance": 5, "total_clicks": 5 },
    "timestamp": 1741420800123
  }
  ```
- **Response (422):** Action validation failed.
- **Response (429):** Rate limit exceeded.

### 13. Load Game Progress
Loads the user's saved progress for a game.

- **Endpoint:** `GET /games/{slug}/load`
- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  {
    "game_state": { "balance": 500, "click_power": 2, "dps": 1.1, "upgrades": {"1": 2}, "total_clicks": 150, "prestige_level": 0 },
    "score": 500,
    "playtime": 3600,
    "last_played": "2026-03-08T10:00:00.000000Z"
  }
  ```

### 14. Save Game Progress
Persists the full game state of the authenticated user and checks for newly unlocked achievements. Called automatically by the frontend every 30 s, on tab hide, and on window close.

- **Endpoint:** `POST /games/{slug}/save`
- **Auth Required:** Yes
- **Body (JSON):**
  ```json
  {
    "game_state": { "balance": 500, "click_power": 2, "dps": 1.1, "upgrades": {"1": 2}, "total_clicks": 150, "prestige_level": 0 },
    "score": 500,
    "playtime": 0
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "saved": true,
    "achievements_unlocked": [
      {
        "id": 2,
        "slug": "clicker-rookie",
        "title": "Clicker Novato",
        "description": "Alcanza 100 puntos.",
        "rarity": "common",
        "points_reward": 25,
        "game_id": 1,
        "unlocked": true,
        "earned_at": "2026-03-08T12:00:00.000000Z"
      }
    ]
  }
  ```

### 15. Complete Game Session
Finalizes a session, saves progress and checks for new achievements.

- **Endpoint:** `POST /games/{slug}/complete`
- **Auth Required:** Yes
- **Body (JSON):**
  ```json
  {
    "session_id": 42,
    "final_score": 1500,
    "duration": 300
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "score": 1500,
    "achievements_unlocked": [
      {
        "id": 2,
        "slug": "clicker-rookie",
        "title": "Clicker Novato",
        "rarity": "common",
        "points_reward": 25
      }
    ]
  }
  ```

---

### 16. Reset Game Progress
Deletes the authenticated user's save and statistics for a specific game. Also abandons any active session. **This action is irreversible.**

- **Endpoint:** `DELETE /games/{slug}/reset`
- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  { "reset": true }
  ```
- **Response (404):** Game not found or inactive.

---

## Achievements

### 17. List All Achievements
Lists all achievements with unlocked status for the authenticated user.

- **Endpoint:** `GET /achievements`
- **Auth Required:** Yes
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "slug": "first-click",
        "title": "Primer Click",
        "description": "Haz tu primer click.",
        "icon_url": null,
        "points_reward": 10,
        "rarity": "common",
        "game_id": 1,
        "unlocked": true,
        "earned_at": "2026-03-08T12:00:00.000000Z"
      }
    ]
  }
  ```

### 18. List Achievements by Game
Lists achievements for a specific game.

- **Endpoint:** `GET /achievements/{slug}`
- **Auth Required:** Yes
- **Response:** Same format as above, filtered by game.

---

## Achievement Condition Types

The `condition` JSON field of each achievement supports the following shapes:

| `field` | Description | Extra keys |
|---|---|---|
| `score` | Current balance/score | — |
| `total_clicks` | Lifetime click count | — |
| `prestige_level` | Number of prestiges performed | — |
| `total_upgrades_bought` | Sum of all upgrades purchased | — |
| `max_upgrade_count` | Highest purchase count for a single upgrade | — |
| `upgrade_count` | Purchase count of a specific upgrade | `upgrade_id` (int) |
| `duration` | Session duration in seconds (for `complete`) | — |

**Operators:** `greater_than`, `greater_than_or_equal`, `equal`, `less_than`, `less_than_or_equal`

**Example:**
```json
{ "field": "upgrade_count", "upgrade_id": 5, "operator": "greater_than_or_equal", "value": 1 }
```

