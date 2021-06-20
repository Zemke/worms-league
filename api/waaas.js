const https = require('https');
const { FormData, fileFromPath } = require('formdata-node');

const api = {};

const post = fd => new Promise((resolve, reject) => {
  const options = {
    hostname: 'waaas.zemke.io',
    port: 443,
    path: '/',
    method: 'POST',
    headers: fd.getHeaders(),
  }
  const req = https.request(options, res => {
    let data = '';
    res.on('data', chunk => data += chunk)
    res.on('end', () => resolve(data))
  })
  req.on('error', err => reject(err))
  fd.pipe(req)
})

// TODO EventEmitter: continue with result as they arrive.
// Together with Zemke/waaas#10 this could be super cool.
api.waaas = files => new Promise(async (resolve, reject) => {
  const result = {}
  for (const file in files) {
    const fd = new FormData()
    fd.set('replay', await fileFromPath(files[file].path))
    try {
      result[file] = { success: await post(fd) }
    } catch (error) {
      console.error('caught error from WAaaS', error);
      result[file] = { error }
    }
  }
  return resolve(result)
});

module.exports = api;

