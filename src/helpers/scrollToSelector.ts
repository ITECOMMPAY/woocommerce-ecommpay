function scrollToSelector(selector: string) {
  document.querySelector(selector)?.scrollIntoView({
    behavior: "smooth",
  })
}

export default scrollToSelector
