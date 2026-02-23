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

- **Endpoint:** `GET /games/{gameId}/leaderboard`
- **Auth Required:** No
- **Parameters:** `gameId` (integer) - The ID of the game.
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
      },
      {
        "user_id": 1,
        "username": "Player One",
        "avatar": null,
        "high_score": 2500,
        "time_played": 3720
      }
    ]
  }
  ```
