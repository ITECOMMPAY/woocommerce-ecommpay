import { useCallback, useState } from "@wordpress/element"

function useBoolean(initialValue: boolean) {
  const [value, setValue] = useState(initialValue)

  const setTrue = useCallback(() => {
    setValue(true)
  }, [])

  const setFalse = useCallback(() => {
    setValue(false)
  }, [])

  const toggle = useCallback(() => {
    setValue((prevValue) => !prevValue)
  }, [])

  return {
    value,
    setTrue,
    setFalse,
    toggle,
  }
}

export default useBoolean
