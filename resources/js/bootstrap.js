import axios from 'axios'

axios.defaults.baseURL = window.location.origin   // http://127.0.0.1:8000

const saved = localStorage.getItem('token')
if (saved) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${saved}`
}

export default axios
