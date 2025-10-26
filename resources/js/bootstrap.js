import axios from 'axios'

axios.defaults.baseURL = window.location.origin 

const saved = localStorage.getItem('token')
if (saved) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${saved}`
}

export default axios
