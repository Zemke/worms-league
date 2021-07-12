function assert(actual, expected) {
  const res = JSON.stringify(actual) === JSON.stringify(expected);
  if (!res) {
    console.log(JSON.stringify({ actual, expected }, null, 2))
    throw Error('assertion failed');
  }
}

function test(title, fn) {
  try {
    console.info('RUNNING', title)
    fn();
    console.info('SUCCESS')
  } catch (err) {
    console.error('FAILURE')
    throw err;
  }
}

function assertTrue(condition) {
  if (condition !== true) throw Error('assertion failed');
}

module.exports = { assert, assertTrue, test };

