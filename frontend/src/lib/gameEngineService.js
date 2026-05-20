import api from './axios'

/**
 * GAME ENGINE SERVICE
 * 
 * Capa de abstracción para la comunicación con el motor de juegos del backend.
 * Centraliza las peticiones de ciclo de vida (play, save, complete, reset).
 */
class GameEngineService {
  /**
   * INICIAR PARTIDA
   * Solicita al servidor crear una sesión y, opcionalmente, cargar el estado previo.
   */
  async play(game, loadSave = true) {
    const { data } = await api.post(`/games/${game}/play`, { load_save: loadSave })
    return data
  }

  /**
   * EJECUTAR ACCIÓN
   * Envía una acción específica al servicio del juego en el backend (ej: "click", "buy").
   */
  async action(game, actionData) {
    const { data } = await api.post(`/games/${game}/action`, {
      action: actionData.action,
      payload: actionData.payload,
      timestamp: Date.now(),
    })
    return data
  }

  /**
   * GUARDADO MANUAL O AUTOMÁTICO
   */
  async save(game, saveData) {
    const { data } = await api.post(`/games/${game}/save`, saveData)
    return data
  }

  /**
   * RECUPERAR ESTADO
   */
  async load(game) {
    const { data } = await api.get(`/games/${game}/load`)
    return data
  }

  /**
   * FINALIZAR SESIÓN
   * Envía la puntuación final para cerrar la sesión y procesar logros.
   */
  async complete(game, completeData) {
    const { data } = await api.post(`/games/${game}/complete`, completeData)
    return data
  }

  /**
   * REINICIAR PROGRESO
   * Elimina permanentemente el save del usuario para el juego indicado.
   */
  async reset(game) {
    const { data } = await api.delete(`/games/${game}/reset`)
    return data
  }
}

export default new GameEngineService()
