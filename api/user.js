const { tx } = require('./tx.js');

async function findOrCreateByUsername(pool, username) {
  return tx(pool, async () => {
    const result = await pool.query(
      'select user_id from username where username=$1',
      [username]);
    if (result.rowCount === 1) {
      return (await pool.query('select * from "user" where id=$1"', [userId])).rows[0];
    } else if (result.rowCount > 1) {
      const matchingUsers = result.rows.map(row => row.id);
      throw Error(`Multiple users (${matchingUsers}) are owning username ${username}.`)
    }
    return (await pool.query('insert into "user" (username) values ($1) returning *', [username])).rows[0];
  });
}

module.exports = { findOrCreateByUsername };


