import api from './axios'

class GameEngineService {
  async play(game, loadSave = true) {
    const { data } = await api.post(`/games/${game}/play`, { load_save: loadSave })
    return data
  }

  async action(game, actionData) {
    const { data } = await api.post(`/games/${game}/action`, {
      action: actionData.action,
      payload: actionData.payload,
      timestamp: Date.now(),
    })
    return data
  }

  async save(game, saveData) {
    const { data } = await api.post(`/games/${game}/save`, saveData)
    return data
  }

  async load(game) {
    const { data } = await api.get(`/games/${game}/load`)
    return data
  }

  async complete(game, completeData) {
    const { data } = await api.post(`/games/${game}/complete`, completeData)
    return data
  }
}

export default new GameEngineService()
