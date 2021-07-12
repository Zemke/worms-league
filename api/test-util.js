function assert(actual, expected) {
  console.assert(
    JSON.stringify(actual) === JSON.stringify(expected),
    JSON.stringify({ actual, expected }, null, 2));
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

module.exports = { assert, test };

