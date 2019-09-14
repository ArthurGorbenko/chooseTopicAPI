function generateHashtag(str) {
  if (str.length === 0 || str.search(/\w/) === -1) {
    return false;
  }
  let modifiedString = capitalize(str);
  console.log(modifiedString.length);
  if (modifiedString.length >= 141) {
    return false;
  } else {
    return "#" + modifiedString;
  }
}

function capitalize(subStr) {
  return subStr
    .split(" ")
    .map(substr => substr.charAt(0).toUpperCase() + substr.slice(1))
    .join("");
}

generateHashtag(
  "Aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa "
);
