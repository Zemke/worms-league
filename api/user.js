const { tx } = require('./tx.js');

async function findOrCreateByUsername(pool, username) {
  return tx(pool, async () => {
    const result = await pool.query(
      'select user_id from username where username=$1',
      [username]);
    if (result.rowCount === 1) {
      return (await pool.query(
          'select * from "user" where id=$1',
          [result.rows[0].user_id])).rows[0];
    } else if (result.rowCount > 1) {
      const matchingUsers = result.rows.map(row => row.id);
      throw Error(`Multiple users (${matchingUsers}) are owning username ${username}.`)
    }
    const user = (await pool.query('insert into "user" default values returning *')).rows[0];
    await pool.query(
        'insert into username (username, user_id) values ($1, $2)',
        [username, user.id]);
    return user;
  });
}

module.exports = { findOrCreateByUsername };


