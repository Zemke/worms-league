const {fromForm} = require('./js/app.js');

const form = [
  {
    value: "patrick",
    name: "username",
  },
  {
    value: "patrick@star.com",
    name: "email",
  },
  {
    value: "Submit",
    name: "",
  },
  "blah",
  {
    something: "else",
  },
  {
    there: {
      is: "more"
    }
  }
];

const actual = fromForm(form);
const expected = {
  username: 'patricks',
  email: 'patrick@star.com'
};
assertThat(actual).isEqualTo(expected);

function assertThat(actual) {
  return {
    isEqualTo(expected) {
      if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        throw new Error(
            `Assertion failed:\n${JSON.stringify(actual)}\nis not equal to\n${JSON.stringify(expected)}`);
      }
    },
  };
}

