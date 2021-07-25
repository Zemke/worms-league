async function tx(pool, fn) {
  const client = await pool.connect();
  try {
    await client.query('begin')
    await fn(client);
    await client.query('commit')
  } catch (e) {
    await client.query('rollback')
    throw e
  } finally {
    client.release()
  }
}

module.exports = { tx };

